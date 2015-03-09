<?php
//sleep(5);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>Document2</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/animate.css/3.2.1/animate.min.css">
	<link rel="stylesheet" href="style.css?<?php time(); ?>">
</head>
<body>

<a href="#mcont1" class="modaled">open1</a>
<div id="mcont1" data-ac-show="rotateIn">modal-content1<br><a href="#mcont4" class="modaled">mcont4</a></div>
<div id="mcont4" data-ac-hide="rollOut">modal-content4</div>

<hr>

<a href="#mcont2" class="modaled">form</a>
<div id="mcont2">
	<form class="form-inline" action="ajax.php" method="post">
  <div class="form-group">
    <label for="exampleInputName2">Name</label>
    <input type="text" name="name" class="form-control" id="exampleInputName2" placeholder="Jane Doe">
  </div>
  <div class="form-group">
    <label for="exampleInputEmail2">Email</label>
    <input type="email" class="form-control" id="exampleInputEmail2" placeholder="jane.doe@example.com">
  </div>
  <button type="submit" class="btn btn-default">Send invitation</button>
</form>
</div>
<div id="mcont3">modal-content3</div>
<hr>

<a href="ajax.php?ajax=1" class="modaled">ajax</a>
<hr>

<a href="https://d13yacurqjgara.cloudfront.net/users/288987/screenshots/1964043/attachments/341580/Homescreen-daytime.jpg?<?php echo time(); ?>" class="modaled">image</a>
<hr>

<div style="height:1000px;background:#eee;">long content</div>

<a href="#mcont1" class="modaled">tester</a>
<hr>


<script>
document.write((!document.addEventListener ? (!document.querySelector ? 'IE7' : 'IE8') : 'Not IE8'));
</script>

<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script src="modal.js?<?php time(); ?>"></script>
<script>
if (!window.console) {
	window.console = {log:function(){},info:function(){}};
}

$('.modaled').modaled();
/*$.modaled('test<br><a href="ajax.php" class="modaled">second</a><hr><div style="width:1500px;background:blue">long content</div>', {
	ovDisableClose: true,
	autoHide:2000,
	header: '<h3>Modal Test</h3><a href="#close" class="hide">&times;</a>',
	footer: '<a href="#" class="hide">Close</a>'
});*/

</script>

</body>
</html>