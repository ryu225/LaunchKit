document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('form[data-validate]').forEach(form => form.addEventListener('submit', event => {
    if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); }
  }));
  const preview=document.getElementById('live-preview');
  if (preview) {
    const name=document.querySelector('[name="business_name"]'), tagline=document.querySelector('[name="tagline"]'), template=document.querySelector('[name="template"]');
    const main=document.getElementById('color-picker'), bg=document.getElementById('secondary-picker'), ink=document.getElementById('text-picker');
    const update=()=>{
      document.getElementById('preview-name').textContent=name.value||'مشروعك يبدأ هنا';
      document.getElementById('preview-tagline').textContent=tagline.value||'وصف قصير يعبر عنك';
      preview.className='live-preview template-'+template.value;
      preview.style.backgroundColor=bg.value; preview.style.color=ink.value;
      preview.style.borderTop='8px solid '+main.value;
    };
    [name,tagline,template,main,bg,ink].forEach(el=>el.addEventListener('input',update));update();
    const logo=document.getElementById('logo-file'), identity=document.getElementById('identity-file'), status=document.getElementById('analysis-status');
    logo.addEventListener('change',()=>{const f=logo.files[0];if(f){const reader=new FileReader();reader.onload=()=>{document.getElementById('preview-logo').src=reader.result;document.getElementById('preview-logo').style.display='block';};reader.readAsDataURL(f);}});
    function rgbHex(r,g,b){return '#'+[r,g,b].map(x=>Math.round(x).toString(16).padStart(2,'0')).join('');}
    function suggestedColors(canvas){
      const ctx=canvas.getContext('2d',{willReadFrequently:true}), pixels=ctx.getImageData(0,0,canvas.width,canvas.height).data, bins=new Map();
      for(let i=0;i<pixels.length;i+=16){if(pixels[i+3]<160)continue;
        const r=pixels[i],g=pixels[i+1],b=pixels[i+2],brightness=(r+g+b)/3,spread=Math.max(r,g,b)-Math.min(r,g,b);
        if(brightness>240||brightness<24||spread<15)continue;
        const key=[r,g,b].map(n=>Math.round(n/32)*32).map(n=>Math.min(255,n)).join(',');bins.set(key,(bins.get(key)||0)+1);
      }
      const sorted=[...bins].sort((a,b)=>b[1]-a[1]); if(!sorted.length)throw new Error('لم نجد ألواناً واضحة في الملف. اختر الألوان يدوياً.');
      const mainRgb=sorted[0][0].split(',').map(Number);const hex=rgbHex(...mainRgb);
      const background=rgbHex(...mainRgb.map(v=>Math.round(248*.88+v*.12)));
      const luminance=(0.2126*mainRgb[0]+0.7152*mainRgb[1]+0.0722*mainRgb[2])/255;
      return {primary:hex,background,text:luminance>0.5?'#222222':'#24202a'};
    }
    async function canvasFromImage(file){const url=URL.createObjectURL(file);try{const img=new Image();img.src=url;await img.decode();const canvas=document.createElement('canvas');const scale=Math.min(1,450/Math.max(img.naturalWidth,img.naturalHeight));canvas.width=Math.max(1,Math.round(img.naturalWidth*scale));canvas.height=Math.max(1,Math.round(img.naturalHeight*scale));canvas.getContext('2d').drawImage(img,0,0,canvas.width,canvas.height);return canvas;}finally{URL.revokeObjectURL(url);}}
    async function canvasFromPdf(file){
      if(!window.pdfjsLib){await new Promise((resolve,reject)=>{const s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';s.onload=resolve;s.onerror=()=>reject(new Error('تعذر تحميل قارئ PDF. جرّب تحويل الصفحة إلى صورة.'));document.head.appendChild(s);});}
      window.pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
      const doc=await window.pdfjsLib.getDocument({data:new Uint8Array(await file.arrayBuffer())}).promise;
      const page=await doc.getPage(1), viewport=page.getViewport({scale:0.8}),canvas=document.createElement('canvas');
      canvas.width=Math.round(viewport.width);canvas.height=Math.round(viewport.height);await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;return canvas;
    }
    document.getElementById('analyze-brand').addEventListener('click',async()=>{
      const file=identity.files[0]||logo.files[0];if(!file){status.textContent='ارفع الشعار أو ملف الهوية أولاً.';return;}
      status.textContent='جارٍ استخراج الألوان من الملف...';
      try{if(file.size>10*1024*1024)throw new Error('ملف الهوية يجب ألا يتجاوز ١٠ ميجابايت.');
        const canvas=file.type==='application/pdf'?await canvasFromPdf(file):await canvasFromImage(file);
        const result=suggestedColors(canvas);main.value=result.primary;bg.value=result.background;ink.value=result.text;update();
        status.textContent='تم اقتراح الألوان من الملف. يمكنك تعديلها باستخدام منتقي الألوان.';
      }catch(ex){status.textContent=ex.message||'تعذر تحليل الملف.';}
    });
  }
});
