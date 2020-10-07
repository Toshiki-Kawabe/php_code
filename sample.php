<?php
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(is_uploaded_file($_FILES['new']['tmp_name'])===true){
            $type=mime_content_type($_FILES['new']['tmp_name']);
            if($type==='image/png' || $type==='image/jpeg'){
                $file_name=substr(base_convert(hash('sha256', uniqid()), 16, 36), 0, 20);
                if($type==='image/png'){
                    $file_name.='.png';
                }else{
                    $file_name.='.jpg';
                }
                move_uploaded_file($_FILES['new']['tmp_name'],'./img/'.$file_name);
            }
        }
    }
?>

<form method='POST' enctype='multipart/form-data'>
    <input type='text' name='name'>
    <input type='file' name='new' accept=".png, .jpeg" required>
    <input type='submit' value='send'>
</form>