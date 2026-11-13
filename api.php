<?php
// PHP 8.1+; all confidential state is stored in an executable-guarded PHP file.
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict']);
ini_set('session.use_strict_mode','1'); session_start();
$cfg=require __DIR__.'/config.php';
function fail(string $m,int $code=400): never {http_response_code($code);echo json_encode(['error'=>$m]);exit;}
function out($v): never {echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function txt($v,int $max=1000): string {if(!is_string($v)||strlen($v)>$max)fail('Teks tidak valid atau terlalu panjang.');return trim($v);}
function imageurl($v): string {$v=txt($v,750000);if($v!==''&&!preg_match('~^https://[^\s]+$|^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$~D',$v))fail('Gunakan URL HTTPS atau gambar PNG/JPG/WebP.');return $v;}
function publicData(array $d): array {$r=['settings'=>$d['settings'],'categories'=>$d['categories'],'pages'=>$d['pages'],'products'=>[]];foreach($d['products'] as $p){$p['stockCount']=count($p['stock']);unset($p['stock']);$r['products'][]=$p;}return $r;}
$prefix="<?php http_response_code(404); exit; ?>\n";
$lock=fopen(__DIR__.'/storage.lock.php','c+');if(!$lock||!flock($lock,LOCK_EX))fail('Penyimpanan tidak dapat dikunci.',503);
$path=__DIR__.'/storage.php';
if(file_exists($path)){$raw=file_get_contents($path);$d=json_decode(substr($raw,strlen($prefix)),true);if(!is_array($d))fail('Data rusak. Pulihkan cadangan melalui hosting.',500);}else{$d=json_decode(file_get_contents(__DIR__.'/data.json'),true);foreach($d['products'] as &$p){$p['stock']=[];}unset($p);$d['orders']=[];$d['admin']=null;$d['version']=1;$d['limits']=[];}
function save(): void {global $d,$path,$prefix;$raw=$prefix.json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$tmp=__DIR__.'/storage.tmp.php';if(file_put_contents($tmp,$raw)===false||!rename($tmp,$path))fail('Gagal menyimpan. Periksa izin folder hosting.',500);@chmod($path,0600);}
function throttle(string $type,int $max): void {global $d;$now=time();foreach($d['limits'] as $k=>$v)if($v['until']<$now)unset($d['limits'][$k]);$key=hash('sha256',($_SERVER['REMOTE_ADDR']??'local').$type);$v=$d['limits'][$key]??['n'=>0,'until'=>$now+900];if($v['n']>=$max)fail('Terlalu banyak percobaan. Tunggu 15 menit.',429);$v['n']++;$d['limits'][$key]=$v;save();}
$action=$_GET['action']??'public';$post=$_SERVER['REQUEST_METHOD']==='POST';$b=[];
if($post){if((int)($_SERVER['CONTENT_LENGTH']??0)>6000000)fail('Permintaan terlalu besar.',413);$b=json_decode(file_get_contents('php://input'),true);if(!is_array($b))fail('JSON tidak valid.');}
$auth=isset($_SESSION['admin'])&&$d['admin']&&$_SESSION['admin']===$d['admin']['version']&&($_SESSION['expires']??0)>time();
if($action==='public')out(publicData($d));
if($action==='session'){$_SESSION['csrf']??=bin2hex(random_bytes(24));out(['auth'=>$auth,'setup'=>!$d['admin'],'csrf'=>$_SESSION['csrf']]);}
if(!$post)fail('Gunakan POST.',405);
if(!hash_equals($_SESSION['csrf']??'!',$_SERVER['HTTP_X_CSRF_TOKEN']??''))fail('Sesi berakhir. Muat ulang halaman.',403);
if($action==='setup'||$action==='login'){
 throttle('login',10);$pw=txt($b['password']??'',200);
 if($action==='setup'){if($d['admin'])fail('Admin sudah dibuat.');if(!hash_equals($cfg['setup_key'],txt($b['key']??'',200)))fail('Kunci instalasi salah.',403);if(strlen($pw)<12)fail('Password minimal 12 karakter.');$d['admin']=['hash'=>password_hash($pw,PASSWORD_DEFAULT),'version'=>bin2hex(random_bytes(16))];save();}
 else if(!$d['admin']||!password_verify($pw,$d['admin']['hash']))fail('Password salah.',401);
 session_regenerate_id(true);$_SESSION['admin']=$d['admin']['version'];$_SESSION['expires']=time()+7200;out(['ok'=>true]);
}
if($action==='order'){
 throttle('order',20);$id=txt($b['product']??'',80);$p=null;foreach($d['products'] as $x)if($x['id']===$id)$p=$x;
 if(!$p||!$p['active'])fail('Produk tidak tersedia.');if(!$p['stock'])fail('Stok sedang habis.');if(!preg_match('/^[1-9][0-9]{7,14}$/D',$d['settings']['whatsapp']))fail('Nomor WhatsApp toko belum diatur.');
 $key=txt($b['key']??'',80);if(!preg_match('/^[a-f0-9]{32}$/D',$key))fail('Kunci pesanan tidak valid.');foreach($d['orders'] as $o)if(($o['request']??'')===$key)out(['order'=>array_intersect_key($o,array_flip(['id','token','title','price','status','created']))]);
 $o=['id'=>'WT-'.strtoupper(bin2hex(random_bytes(5))),'token'=>bin2hex(random_bytes(20)),'request'=>$key,'product'=>$id,'title'=>$p['title'],'price'=>$p['price'],'status'=>'pending','created'=>gmdate('c'),'delivery'=>'','auto'=>$p['auto']];$d['orders'][]=$o;save();out(['order'=>array_intersect_key($o,array_flip(['id','token','title','price','status','created']))]);
}
if($action==='check'){
 throttle('check',120);foreach($d['orders'] as $o)if($o['id']===($b['id']??'')&&hash_equals($o['token'],(string)($b['token']??''))){unset($o['request'],$o['token'],$o['assigned']);out($o);}fail('Kode pesanan atau kunci akses salah.',404);
}
if(!$auth)fail('Silakan login admin.',401);
if($action==='admin'){$safe=$d;unset($safe['admin'],$safe['limits']);out(['data'=>$safe,'csrf'=>$_SESSION['csrf']]);}
if($action==='logout'){$_SESSION=[];session_destroy();out(['ok'=>true]);}
if($action==='password'){
 if(!password_verify(txt($b['current']??'',200),$d['admin']['hash']))fail('Password lama salah.');$pw=txt($b['password']??'',200);if(strlen($pw)<12)fail('Password minimal 12 karakter.');$d['admin']['hash']=password_hash($pw,PASSWORD_DEFAULT);$d['admin']['version']=bin2hex(random_bytes(16));$_SESSION['admin']=$d['admin']['version'];save();out(['ok'=>true]);
}
if($action==='save'){
 if(($b['version']??0)!==$d['version'])fail('Data telah berubah. Muat ulang panel sebelum menyimpan.',409);
 $kind=$b['kind']??'';$v=$b['value']??null;
 if($kind==='settings'){
  $s=$d['settings'];foreach(['title','description','bannerTitle','bannerDescription','footer','googleVerification'] as $k)$s[$k]=txt($v[$k]??'',2000);
  $s['whatsapp']=txt($v['whatsapp']??'',20);if($s['whatsapp']!==''&&!preg_match('/^[1-9][0-9]{7,14}$/D',$s['whatsapp']))fail('Nomor WhatsApp harus format internasional, contoh 628123456789.');
  $s['telegram']=txt($v['telegram']??'',500);if($s['telegram']!==''&&!preg_match('~^https://t\.me/[A-Za-z0-9_/?=&-]+$~D',$s['telegram']))fail('Link Telegram harus https://t.me/...');
  $s['logo']=imageurl($v['logo']??'');$s['favicon']=imageurl($v['favicon']??'');$d['settings']=$s;
 }elseif($kind==='categories'){
  if(!is_array($v)||count($v)>50)fail('Maksimal 50 kategori.');$cats=[];foreach($v as $c){$c=txt($c,60);if($c!=='')$cats[]=$c;}$cats=array_values(array_unique($cats));foreach($d['products'] as $p)if(!in_array($p['category'],$cats,true))fail('Kategori masih dipakai produk.');$d['categories']=$cats;
 }elseif($kind==='product'){
  $id=txt($v['id']??'',80);$old=null;$idx=null;foreach($d['products'] as $i=>$p)if($p['id']===$id){$old=$p;$idx=$i;}
  $p=['id'=>$old?$id:bin2hex(random_bytes(8)),'title'=>txt($v['title']??'',100),'description'=>txt($v['description']??'',1500),'category'=>txt($v['category']??'',60),'duration'=>txt($v['duration']??'',60),'symbol'=>txt($v['symbol']??'',12),'color'=>txt($v['color']??'',7),'image'=>imageurl($v['image']??''),'price'=>filter_var($v['price']??null,FILTER_VALIDATE_INT),'auto'=>!empty($v['auto']),'active'=>!empty($v['active']),'stock'=>$old['stock']??[]];
  if(!$p['title']||$p['price']===false||$p['price']<0||$p['price']>100000000||!in_array($p['category'],$d['categories'],true)||!preg_match('/^#[0-9a-fA-F]{6}$/D',$p['color']))fail('Periksa judul, harga, kategori, dan warna.');if($idx===null)$d['products'][]=$p;else $d['products'][$idx]=$p;
 }elseif($kind==='stock'){
  $found=false;foreach($d['products'] as &$p)if($p['id']===($v['id']??'')){$found=true;if(!is_array($v['stock'])||count($v['stock'])>3000)fail('Maksimal 3.000 stok per produk.');$p['stock']=[];foreach($v['stock'] as $s){$s=txt($s,3000);if($s!=='')$p['stock'][]=$s;}}unset($p);if(!$found)fail('Produk tidak ditemukan.');
 }elseif($kind==='pages'){
  if(!is_array($v)||count($v)>30)fail('Maksimal 30 halaman.');$pages=[];$slugs=[];foreach($v as $p){$slug=txt($p['slug']??'',60);if(!preg_match('/^[a-z0-9-]+$/D',$slug)||in_array($slug,$slugs))fail('Slug halaman tidak valid atau duplikat.');$slugs[]=$slug;$pages[]=['slug'=>$slug,'title'=>txt($p['title']??'',100),'content'=>txt($p['content']??'',15000)];}$d['pages']=$pages;
 }else fail('Jenis data tidak valid.');
 $d['version']++;save();out(['ok'=>true]);
}
if($action==='deleteProduct'){$id=txt($b['id']??'',80);foreach($d['orders'] as $o)if($o['product']===$id&&$o['status']==='pending')fail('Produk memiliki pesanan pending. Batalkan pesanan atau nonaktifkan produk.');$d['products']=array_values(array_filter($d['products'],fn($p)=>$p['id']!==$id));$d['version']++;save();out(['ok'=>true]);}
if($action==='orderStatus'){
 $idx=null;foreach($d['orders'] as $i=>$o)if($o['id']===($b['id']??''))$idx=$i;if($idx===null)fail('Pesanan tidak ditemukan.');$o=$d['orders'][$idx];$status=$b['status']??'';
 if($status==='delete'){if($o['status']==='pending')fail('Batalkan pesanan terlebih dahulu.');array_splice($d['orders'],$idx,1);}
 elseif($status==='cancel'){if($o['status']!=='pending')fail('Hanya pesanan pending dapat dibatalkan.');$d['orders'][$idx]['status']='cancel';}
 elseif($status==='paid'){if($o['status']!=='pending')fail('Pesanan sudah diproses.');$found=false;foreach($d['products'] as &$p)if($p['id']===$o['product']){if(!$p['stock'])fail('Stok habis. Tambahkan stok sebelum menandai Paid.');$item=array_shift($p['stock']);$found=true;}unset($p);if(!$found)fail('Produk tidak ditemukan.');$d['orders'][$idx]['status']='paid';$d['orders'][$idx]['assigned']=$item;if($o['auto'])$d['orders'][$idx]['delivery']=$item;}
 elseif($status==='deliver'){if($o['status']!=='paid')fail('Konfirmasi pembayaran dahulu.');$d['orders'][$idx]['delivery']=$o['assigned']??'';}
 else fail('Status tidak valid.');$d['version']++;save();out(['ok'=>true]);
}
fail('Aksi tidak dikenal.',404);
