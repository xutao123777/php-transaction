//事务, 可用于嵌套, 以最外层事务为准
function transaction(Closure $closure, $mysqli){  
	static $transactionIndex = 0;
	if (! $transactionIndex) {
		$mysqli->autocommit(0);    //暂时关闭MYSQL事务机制的自动提交模式
	}

	$transactionIndex++;

	try {
		$result = $closure();   //匿名函数返回的结果 如: true false
		if ($transactionIndex === 1) {
			$mysqli->commit(); 
		}
		return $result;
	} catch (\Exception $e) {
		if ($transactionIndex === 1) {
			$mysqli->rollback();
		}
		throw $e;
	} finally {
		$transactionIndex--;
	}
}




//使用方法:
$mysqli = new mysqli("localhost", "root", "root", "btcbing_com", 3307);     //连接MySQL数据库
if (mysqli_connect_errno()) {                                         //检查连接错误
	printf("连接失败: %s<br>", mysqli_connect_error());
	exit();
} 


                                             //设置事务执行状态
$price=8000;                                 //转账的数目

$res =  transaction(function()use($price){              
	global $mysqli;                          //将$mysqli变量引用
	
	$success = true; 
 	//执行从6296记录中减少$price的值，返回1表示成功，否则执行失败
	$result=$mysqli->query("UPDATE tw_user_coin SET jwe=jwe-$price WHERE userid=6296");
	//如果SQL语句执行失败或没有改变记录中的值，将$sucess的值设置为FALSE
	if(!$result or $mysqli->affected_rows !=1) {
		$success = false;                      //设置$sucess的值为FALSE
	}
	
    //执行向6297记录中添加$price的值，返回1表示成功，否则执行失败
	$result=$mysqli->query("UPDATE tw_user_coin SET jwe=jwe+$price WHERE userid=6297");
	//如果SQL语句执行失败或没有改变记录中的值，将$sucess的值设置为FALSE
	if(!$result or $mysqli->affected_rows !=1) {
		$success = false;                      //设置$sucess的值为FALSE
	}

	if($success){                               //如果$success的值为TRUE
		return true;                            //转账成功
	}else{                                     //如果$success的值为FLASE，事务中有错误
		return false;                          //转账失败              
	}

	$mysqli->close();                             //关闭与MySQL数据库的连接
}, $mysqli);                                      //将$mysqli传递到函数中


if($res){
	echo "转账成功";
}else{
	echo "转账失败";
}
