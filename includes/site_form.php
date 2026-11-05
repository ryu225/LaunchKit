<?php
$templates=['classic','playful','minimal'];
$types=['food'=>'مطعم أو مقهى','shop'=>'متجر ومنتجات','booking'=>'مواعيد وخدمات','portfolio'=>'أعمال وخدمات احترافية','other'=>'نشاط آخر'];
$validColor=static fn($v)=>is_string($v)&&preg_match('/^#[0-9a-fA-F]{6}$/D',$v);
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 $business=trim($_POST['business_name']??'');$tagline=trim($_POST['tagline']??'');$description=trim($_POST['description']??'');$services=trim($_POST['services']??'');$contact=trim($_POST['contact']??'');
 $template=(string)($_POST['template']??'classic');$slug=site_slug((string)($_POST['slug']??''));
 $color=(string)($_POST['color']??'#d84937');$secondary=(string)($_POST['secondary_color']??'#fff2e6');$ink=(string)($_POST['text_color']??'#252021');
 $type=(string)($_POST['business_type']??'other');$logo=$site['logo_path']??'';
 if (mb_strlen($business)<2 || mb_strlen($business)>120 || !$slug || strlen($slug)>80 || mb_strlen($description)>1000 || mb_strlen($services)>1000 || mb_strlen($tagline)>200 || mb_strlen($contact)>190 || !in_array($template,$templates,true) || !isset($types[$type]) || !$validColor($color) || !$validColor($secondary) || !$validColor($ink)) $error='تأكد من البيانات المدخلة والألوان المستخدمة.';
 if (!$error && isset($_FILES['logo']) && $_FILES['logo']['error']!==UPLOAD_ERR_NO_FILE) {
  if ($_FILES['logo']['error']!==UPLOAD_ERR_OK || $_FILES['logo']['size']>2*1024*1024) $error='يجب أن يكون الشعار صورة بحجم أقل من ٢ ميجابايت.';
  else {
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);$ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
   if (!$ext || !getimagesize($_FILES['logo']['tmp_name'])) $error='صيغة الشعار غير مدعومة. استخدم PNG أو JPG أو WebP.';
   else { $newFile='uploads/'.bin2hex(random_bytes(18)).'.'.$ext;
     if (!move_uploaded_file($_FILES['logo']['tmp_name'],__DIR__.'/../'.$newFile)) $error='تعذر حفظ صورة الشعار.';
     else $logo=$newFile;
   }
  }
 }
 if (!$error) { try {
  if ($editing) {$st=db()->prepare('UPDATE sites SET business_name=?,tagline=?,description=?,services=?,contact=?,template=?,color=?,secondary_color=?,text_color=?,logo_path=?,business_type=?,slug=? WHERE id=? AND user_id=?');$st->execute([$business,$tagline,$description,$services,$contact,$template,$color,$secondary,$ink,$logo,$type,$slug,$site['id'],user_id()]);}
  else {$st=db()->prepare('INSERT INTO sites(user_id,business_name,tagline,description,services,contact,template,color,secondary_color,text_color,logo_path,business_type,slug) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');$st->execute([user_id(),$business,$tagline,$description,$services,$contact,$template,$color,$secondary,$ink,$logo,$type,$slug]);}
  redirect('dashboard.php?saved=1');
 } catch(PDOException $ex) {if($ex->getCode()==='23000')$error='الرابط مستخدم بالفعل.';else throw $ex;} }
}
header_html($editing?'تعديل الموقع':'إنشاء موقع'); ?>
<section class="wrapper section form-layout"><div class="form-main"><span class="eyebrow">مساحة التصميم</span><h1 class="page-title"><?= $editing?'عدّل موقعك':'صمّم علامتك وموقعك.' ?></h1><p class="lead">ارفع شعارك أو دليل الهوية، واقترح الألوان تلقائياً، ثم خصصها كما تريد.</p>
<?php if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" data-validate><?=csrf_input()?>
<div class="field-row"><label>اسم المشروع *<input name="business_name" required minlength="2" maxlength="120" value="<?=form_value('business_name',$site)?>" placeholder="مخبز لوز"></label><label>الرابط المختصر *<input name="slug" required pattern="[a-zA-Z0-9-]+" maxlength="80" value="<?=form_value('slug',$site)?>" placeholder="loz-bakery" dir="ltr"></label></div>
<label>نوع النشاط<select name="business_type" id="business-type"><?php foreach($types as $key=>$label):?><option value="<?=e($key)?>" <?= (($_POST['business_type']??$site['business_type']??'other')===$key)?'selected':'' ?>><?=e($label)?></option><?php endforeach;?></select></label>
<div class="brand-analyzer"><h2>✦ محلّل الهوية البصرية</h2><p>أضف شعارك أو صورة من دليل الهوية. نقترح ألواناً من الصورة مباشرة على جهازك، ويمكنك تعديل أي لون بعدها.</p><label>الشعار (PNG / JPG / WebP) <input name="logo" type="file" id="logo-file" accept="image/png,image/jpeg,image/webp"></label><label>ملف الهوية للتحليل (صورة أو PDF)<input type="file" id="identity-file" accept="image/png,image/jpeg,image/webp,application/pdf"></label><button class="button secondary" type="button" id="analyze-brand">تحليل الهوية واقتراح الألوان ✦</button><p class="analysis-status" id="analysis-status" aria-live="polite">التحليل يعتمد على ألوان الصورة؛ لا يتطلب API مدفوعاً. ملفات PDF تحتاج اتصالاً بالإنترنت لتحميل قارئ PDF.</p><div class="field-row"><label>اللون الرئيسي<input type="color" name="color" id="color-picker" value="<?=e($validColor($_POST['color']??$site['color']??'')?($_POST['color']??$site['color']):'#d84937')?>"></label><label>لون الخلفية<input type="color" name="secondary_color" id="secondary-picker" value="<?=e($validColor($_POST['secondary_color']??$site['secondary_color']??'')?($_POST['secondary_color']??$site['secondary_color']):'#fff2e6')?>"></label></div><label>لون النص<input type="color" name="text_color" id="text-picker" value="<?=e($validColor($_POST['text_color']??$site['text_color']??'')?($_POST['text_color']??$site['text_color']):'#252021')?>"></label></div>
<label>عبارة تعريفية<input name="tagline" maxlength="200" value="<?=form_value('tagline',$site)?>"></label><label>عن النشاط<textarea name="description" maxlength="1000" rows="4"><?=form_value('description',$site)?></textarea></label><label>الخدمات والمنتجات<textarea name="services" maxlength="1000" rows="3"><?=form_value('services',$site)?></textarea></label><label>تواصل<input name="contact" maxlength="190" value="<?=form_value('contact',$site)?>"></label>
<label>أسلوب التصميم<select name="template" id="template-select"><option value="classic" <?= (($_POST['template']??$site['template']??'classic')==='classic')?'selected':'' ?>>كلاسيكي</option><option value="playful" <?= (($_POST['template']??$site['template']??'classic')==='playful')?'selected':'' ?>>مرح</option><option value="minimal" <?= (($_POST['template']??$site['template']??'classic')==='minimal')?'selected':'' ?>>بسيط</option></select></label>
<button class="button primary" type="submit"><?= $editing?'حفظ التغييرات':'إنشاء الموقع' ?> ↗</button> <a class="button secondary" href="dashboard.php">إلغاء</a>
</form></div><aside class="form-aside"><h3>معاينة الهوية مباشرة</h3><div class="live-preview" id="live-preview"><img id="preview-logo" alt="الشعار" style="max-width:100px;max-height:85px;object-fit:contain;<?=empty($site['logo_path'])?'display:none;':''?>" <?php if(!empty($site['logo_path'])):?>src="<?=e($site['logo_path'])?>"<?php endif;?>><small>موقعك الجديد</small><strong id="preview-name">مشروعك يبدأ هنا</strong><p id="preview-tagline">وصف قصير يعبر عنك</p></div><p>يمكنك تعديل الألوان يدوياً بعد التحليل. النتائج اقتراحات أولية وليست تقييماً إبداعياً كاملاً للهوية.</p></aside></section><?php footer_html(); ?>
