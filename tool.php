<?php
    $host = 'localhost';
    $username = 'codecamp38056';
    $passwd = 'codecamp38056';
    $dbname = 'codecamp38056';
    
    //$drink_data = [];
    $name = '';
    $price = '';
    $quantity = '';
    $image = '';
    $stock = '';
    $status = '';
    $err = [];
    $comment = '';
    
    //DB接続
    if ($link = mysqli_connect($host, $username, $passwd, $dbname)) {
        //文字コードセット
        mysqli_set_charset($link, 'UTF8');
        
        //追加ボタン押下時の処理
        if (isset($_POST['add'])) {
            if (is_uploaded_file($_FILES['image']['tmp_name']) === TRUE ) {
                //ファイル形式を取得
                $type = mime_content_type($_FILES['image']['tmp_name']);
            }
            
            //トランザクション開始(オートコミットをオフ)
            mysqli_autocommit($link, FALSE);
            
            /*
            商品名・値段・在庫数・公開ステータスの順にif文を作っていく
            以下、商品名の入力判定
            */
            
            //商品名の前後の全角/半角スペースを除去
            $name = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['name']);
            
            //商品名の入力文字判定
            if (mb_strlen($name) === 0) {
                $err[] = '商品名を入力してください';
            }
            
            /*
            商品名・値段・在庫数・公開ステータスの順にif文を作っていく
            以下、値段と在庫数の入力判定
            */
            
            //値段と在庫数の前後の全角/半角スペースを除去
            $price = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['price']);
            $quantity = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['quantity']);
            
            //値段の入力文字判定
            if (mb_strlen($price) === 0) {
                $err[] = '値段を入力してください';
            } else if (preg_match('/^[1-9][0-9]*/', $price) === 0) {
                $err[] = '値段は0以上の整数としてください';
            }
            
            //在庫数の入力文字判定
            if (mb_strlen($quantity) === 0) {
                $err[] = '在庫数を入力してください';
            } else if (preg_match('/^[1-9][0-9]*/', $quantity) === 0) {
                $err[] = '在庫数は0以上の整数としてください';
            }
            
            /*
            商品名・値段・在庫数・公開ステータスの順にif文を作っていく
            以下、公開ステータスの入力判定
            */            
            
            $status = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['status']);
            
            if($status !== '1' && $status !== '0') {
                $err[] = '公開ステータスは「公開」or「非公開」で商品を追加してください。';
            }
            
            /*
            以下、送信ファイルの入力判定
            */
            
            //送信ファイルの拡張チェック
            if ($type === 'image/png' || $type === 'image/jpeg' ) {
                
                //name被り防止の為にランダムに20文字を取得し、それをファイル名に置き換える
                $file_name = substr(base_convert(hash('sha256', uniqid()), 16, 19), 0, 20);
                
                //拡張子を付与
                if ($type === 'image/png') {
                    $file_name .= '.png';
                } else {
                    $file_name .= '.jpg';
                }
                
                //保存場所の指定
                move_uploaded_file($_FILES['image']['tmp_name'], $file_name);
                
                /**
                 * 以下、画像ファイルの処理 
                 */                
                
            } else {
                //エラーメッセージの追加
                $err[] = 'ファイル形式選択エラー。「.png」「.jpg」 のみ選択できます。';
            }
            
            //エラーメッセージ0(->すべて正しく入力されている)の場合、SQLでdrink_data_tableに追加する
            if (count($err) === 0) {
                $comment = '商品の追加が完了しました';
                
                $date = date('Y-m-d H:i:s');
                
                $add = $name . '\',' . $price . ',\'' . $date . '\',\'' . $date . '\',' . $status . ',\'' . $file_name;
                
                //SQL
                $sql = 'INSERT INTO drink_data_table (drink_name, price, created_at, update_at, status, img)
                        VALUES (\'' . $add . '\')';
                var_dump($sql);
                $result = mysqli_query($link, $sql); 
                
            }
        }
        //drink_data_tableのデータ取得SQL
        $sql = 'SELECT drink_id, drink_name, price, created_at, update_at, status, img
                FROM drink_data_table';
        
        //SQL実行        
        if($result = mysqli_query($link, $sql)) {
            while ($row = mysqli_fetch_array($result)) {
                $drink_data[] = $row;
            }
        }
        
        var_dump($drink_data);
        
        mysqli_free_result($result);
        mysqli_close($link);     
    } else {
        print 'DB接続失敗';
    }
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>自動販売機</title>
        <style type="text/css">
            table, td, th {
                border: solid black 1px;
            }
            
            table {
                width: 1000px;
            }
            
            .private {
                background-color: silver;
            }
        </style>
    </head>
    <body>
        <!-- POST送信されたときのコメント表示 --->
        <?php foreach ($err as $put) { ?>
            <p><?php print $put; ?></p>
        <?php } ?>
        <?php if(isset($comment) === TRUE) { print $comment; } ?>
        
        <!-- 商品追加フォーム --->
        <h1>自動販売機管理ツール</h1>
        <h2>新規商品追加</h2>
        <form method="post" enctype="multipart/form-data">
            名前:<input type="text" name="name"/><br>
            値段:<input type="text" name="price"/><br>
            個数:<input type="text" name="quantity"/><br>
            <input type="file" name="image" accept=".png, .jpeg" required/><br>
            <select name="status">
                <option value='0'>非公開</option>
                <option value='1'>公開</option>
            </select><br>
            <input type="submit" name="add" value="---商品追加---"/>
        </form>
        
        <!-- 商品一覧 --->
        <h2>商品情報変更</h2>
        <table>
            <caption>商品一覧</caption>
            <tr>
                <th>商品画像</th>
                <th>商品名</th>
                <th>価格</th>
                <th>在庫数</th>
                <th>ステータス</th>
            </tr>
            <?php foreach ($drink_data as $value) { ?>
                <?php if ($value['status'] === '0') { ?>
                    <tr class="private">
                <?php } else { ?>
                    <tr>
                <?php } ?>
                        <td><img src="<?php print $value['img']; ?>"></td>
                        <td><?php print htmlspecialchars($value['drink_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</td>
                        <td><form method="post"><input type="text" name="stock"/>個&nbsp;<input type="submit" value="変更"/></form></td>
                        <td>
                            <form method="post">
                            <?php if ($value['status'] === '1') { ?>
                                <button type="submit" name="status" value="0"/>公開→非公開</button>
                            <?php } else { ?>
                                <button type="submit" name="status" value="1"/>非公開→公開</button>
                            <?php } ?>    
                            </form>
                        </td>
                    </tr>
            <?php } ?>
        </table>
        
    </body>
</html>
