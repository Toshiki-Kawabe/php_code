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
        mysqli_set_charset($link, 'utf8');
        
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
                move_uploaded_file($_FILES['image']['tmp_name'],'./img/'.$file_name);
                
                /**
                 * 以下、画像ファイルの処理 
                 */                
                
            } else {
                //エラーメッセージの追加
                $err[] = 'ファイル形式選択エラー。「.png」「.jpg」 のみ選択できます。';
            }
            
            //エラーメッセージ0(->すべて正しく入力されている)の場合、SQLでdrink_data_tableに追加する
            if (count($err) === 0) {

                $date = date('Y-m-d H:i:s');
                
                $add_data = $name . '\',' . $price . ',\'' . $date . '\',\'' . $date . '\',' . $status . ',\'' . $file_name;

                /*INSERTが保存されない問題が解決されたら
                IDを合わせるため両テーブルのレコードをリセットする*/
                /*ここの2つはトランザクションを入れたほうがよさそうな気がする*/
                
                //SQL@drink_data_table
                $sql = 'INSERT INTO drink_data_table (drink_name, price, created_at, update_at, status, img)
                        VALUES (\'' . $add_data . '\')';
                //var_dump($sql);
                if ($result = mysqli_query($link, $sql)) { 
                    
                    //drink_data_tableで追加したdrink_idを取得
                    $drink_id = mysqli_insert_id($link);
                    //var_dump($drink_id);
                    
                    $add_con = $drink_id . ',' . $quantity . ',\'' . $date . '\',\'' . $date;
                    
                    //SQL@inventory_control_table
                    $sql = 'INSERT INTO inventory_control_table (drink_id, stock_quantity, created_at, update_at)
                            VALUES (' . $add_con . '\')';
                    //var_dump($sql);
                    if ($result = mysqli_query($link, $sql)) {
                        $comment = '商品の追加が完了しました';
                    } else {
                        $err[] = 'SQL失敗:' . $sql;
                    }
                } else {
                    $err[] = 'SQL失敗:' . $sql;
                }
            }
            //トランザクション成否判定
            if (count($err) === 0) {
                //処理確定
                mysqli_commit($link);
            } else {
                mysqli_rollback($link);
            } 
        }
        
        //在庫数変更時の処理
        if (isset($_POST['stock'])) {
            $quantity = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['stock']);
            $drink_id = $_POST['id'];
            
            //在庫数の入力文字判定
            if (preg_match('/^[1-9][0-9]*/', $quantity) === 0) {
                $err[] = '在庫数は0以上の整数としてください';
            } else {
                //在庫数が変更されずにボタンが押されたかどうかを確認する
                $sql = 'SELECT stock_quantity
                        FROM inventory_control_table
                        WHERE drink_id = \'' . $drink_id . '\'';
                
                //SQL実行        
                if($result = mysqli_query($link, $sql)) {
                    while ($row = mysqli_fetch_array($result)) {
                        $stock_data[] = $row;
                    }
                    
                    if ($stock_data[0]['stock_quantity'] === $quantity) {
                        $err[] = '在庫数が変更されていません';
                    } else {
                        //更新日時を取得
                        $date = date('Y-m-d H:i:s');
                        
                        //レコード更新のSQL文
                        $sql = 'UPDATE inventory_control_table
                                SET update_at = \'' . $date . '\'
                                    ,stock_quantity = \'' . $quantity . '\'    
                                WHERE drink_id = \'' . $drink_id . '\''; 
            
                        if ($result = mysqli_query($link, $sql)) {
                            $comment = '在庫数の変更が完了しました';
                        }                        
                    }
                }            
            }    
        }
        
        //ステータス変更時の処理
        if (isset($_POST['status'])) {
            $status = $_POST['status'];
            $drink_id = $_POST['id'];
            
            //更新日時を取得
            $date = date('Y-m-d H:i:s');

            //レコード更新のSQL文
            $sql = 'UPDATE drink_data_table
                    SET update_at = \'' . $date . '\'
                        ,status = \'' . $status . '\'    
                    WHERE drink_id = \'' . $drink_id . '\''; 

            if ($result = mysqli_query($link, $sql)) {
                $comment = '公開ステータスの変更が完了しました';
            }
        }
        
        //テーブル情報取得
        $sql = 'SELECT ddt.drink_id, ddt.drink_name, ddt.img, ddt.price, ddt.status, ict.stock_quantity
                FROM drink_data_table AS ddt
                INNER JOIN inventory_control_table AS ict
                ON ddt.drink_id = ict.drink_id ';
        
        //SQL実行        
        if($result = mysqli_query($link, $sql)) {
            while ($row = mysqli_fetch_array($result)) {
                $drink_data[] = $row;
            }
        }

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
                        <td><img src="/drink/img/<?php print $value['img']; ?>"></td>
                        <td><?php print htmlspecialchars($value['drink_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php print htmlspecialchars($value['price'], ENT_QUOTES, 'UTF-8'); ?>円</td>
                        <td>
                            <form method="post">
                                <input type="text" name="stock" value="<?php print $value['stock_quantity'] ?>"/>個&nbsp;
                                <input type="hidden" name="id" value="<?php print $value['drink_id']; ?>"/>
                                <input type="submit" value="変更"/>
                            </form>
                        </td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="id" value="<?php print $value['drink_id']; ?>"/>                                
                            <?php if ($value['status'] === '1') { ?>
                                <input type="hidden" name="status" value="0"/>                                
                                <input type="submit" value="公開→非公開"/>                            
                            <?php } else { ?>
                                <input type="hidden" name="status" value="1"/>
                                <input type="submit" value="非公開→公開"/>
                            <?php } ?>
                            </form>
                        </td>
                    </tr>
            <?php } ?>
        </table>
    </body>
</html>
