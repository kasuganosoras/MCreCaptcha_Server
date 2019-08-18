<?php
error_reporting(E_ALL);
define("REC_SITEKEY", "在这里写你的前端 key");	    // reCaptcha 前端验证 Key
define("REC_SITEPWD", "在这里写你的后端 Key");	    // reCaptcha 后端验证 Key
define("VERIFY_MCIP", false);                       // 是否启用 IP 地址验证
define("VERIFY_SITE", "http://192.168.3.233:980/"); // 验证地址，显示在客户端里
define("SERVER_HTTP", 980);                         // 网页端口
define("SERVER_SOCK", 981);                         // 服务器验证端口
define("TIMEOUT_SET", 600);                         // 超过指定时间后需要重新验证
define("WORKERS_NUM", 4);                           // 验证服务器工作线程数

// 生成随机请求密码
define("CLIENT_PASS", sha1(md5(time() . mt_rand(0, 9999999) . __DIR__ . __FILE__)));

// 创建服务器
$server = new swoole_http_server("0.0.0.0", SERVER_HTTP);
$server->set(array('daemonize'=> false));

// 接收到请求
$server->on('request', function($request, $response) {
	
	// 参数传统化
	$_GET = $request->get ?? Array();
	$_POST = $request->post ?? Array();
	$_COOKIE = $request->cookie ?? Array();
	$_FILES = $request->files ?? Array();
	$_REQURI = $request->server['request_uri'] ?? "/";
	
	$server_id = "";
	$server_login = "";
	if(isset($_REQURI) && $_REQURI !== "/") {
		$server_id = str_replace("/", "", $_REQURI);
		if(!preg_match("/^[a-z0-9]{7}$/", $server_id)) {
			$response->end("<script>location='/';</script>");
			return $response;
		}
		$server_info  = getServerInfo($server_id);
		if(!$server_info || time() - $server_info['time'] > 15) {
			$response->end("<script>location='/';</script>");
			return $response;
		}
		$server_login = "<p class='text-center'><small>正在登入服务器：" . htmlspecialchars($server_info['name']) . "</small></p>";
	}
		
	$response->header("Server", "ZeroDream/Lfs1.0");
	if(strtolower($request->server['request_method']) == "get") {
		$recaptcha_key = REC_SITEKEY;
		$year = date("Y");
		$data = <<<EOF
<!DOCTYPE HTML>
<html lang="zh_CN">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=11">
		<meta name="msapplication-TileColor" content="#F1F1F1">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" crossorigin="anonymous">
		<script src="https://cdn.staticfile.org/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
		<script src="https://www.recaptcha.net/recaptcha/api.js?render={$recaptcha_key}" defer></script>
		<title>MC reCaptcha v2</title>
		<style type="text/css">.full-width{width:100%;}.logo{font-weight:400;}body:before{content:"";display:block;position:fixed;left:0;top:0;width:100%;height:100%;z-index:-10;}body,body:before{background-color:#000;background-image:url(https://i.loli.net/2019/08/13/7EqLWfi1tw6M2Qn.jpg);background-size:cover;background-position:center;background-attachment:fixed;background-repeat:no-repeat;-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;}.main-box{width:100%;background:rgba(255,255,255,0.9);border:32px solid rgba(0,0,0,0);border-bottom:16px solid rgba(0,0,0,0);box-shadow:0px 0px 32px rgba(0,0,0,0.75);}.copyright{position:fixed;bottom:16px;left:32px;color:#FFF;font-size:16px;text-shadow:0px 0px 8px rgba(0,0,0,0.75);}@media screen and (max-width:992px){.padding-content{display:none;}.main-content{width:100%;max-width:100%;flex:0 0 100%;}.main-box{width:70%;}}@media screen and (max-width:768px){.padding-content{display:none;}.main-content{width:100%;max-width:100%;flex:0 0 100%;}.main-box{width:100%;}}</style>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-sm-3 padding-content"></div>
				<div class="col-sm-6 main-content">
					<table style="width: 100%;height: 100vh;">
						<tr style="height: 100%;">
							<td style="height: 100%;padding-bottom: 64px;">
								<center>
									<div class="main-box text-left">
										<h2 class="logo">MC reCaptcha v2</h2>
										<p>Minecraft 我的世界服务器防压测系统</p>
										{$server_login}
										<hr>
										<form method="POST">
											<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
											<p><b>请输入你的游戏名</b></p>
											<p><input type="text" class="form-control" name="username" id="username" /></p>
											<p><button type="submit" class="btn btn-primary full-width">完成认证</button></p>
										</form>
									</div>
								</center>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<p class="copyright">&copy; {$year} ZeroDream</p>
		<script type="text/javascript">
			window.onload = function() {
				grecaptcha.ready(function() {
					grecaptcha.execute('{$recaptcha_key}', {action:'validate_captcha'}).then(function(token) {
						document.getElementById('g-recaptcha-response').value = token;
					});
				});
			}
		</script>
	</body>
</html>
EOF;
		$response->end($data);
	} else {
		if(isset($_POST['username']) && isset($_POST['g-recaptcha-response'])) {
			if(preg_match("/^[A-Za-z0-9\_\-]{1,32}$/", $_POST['username'])) {
				if(recaptchaVerify($_POST['g-recaptcha-response'])) {
					$json = Json_Encode(Array(
						'token'     => CLIENT_PASS,
						'action'    => 'whitelist',
						'username'  => $_POST['username'],
						'ip'        => $request->server['remote_addr'],
						'serverid'  => $server_id
					));
					$client = new swoole_client(SWOOLE_SOCK_TCP);
					if (!$client->connect('127.0.0.1', SERVER_SOCK, -1)) {
						$msg = "通知后端时出错：{$client->errCode}";
						$client->close();
					} else {
						$client->send("{$json}\n");
						echo "Server return: " . $client->recv() . "\n";
						$client->close();
						$msg = "验证成功，现在您可以进入游戏了。";
					}
				} else {
					$msg = "很抱歉，您没有通过机器人验证。";
				}
			} else {
				$msg = "您的用户名不合法，请重新输入。";
			}
		} else {
			$msg = "请您提交完整的信息。";
		}
		$response->end("<meta charset='utf-8'><script>alert('{$msg}');location='{$_REQURI}';</script>");
	}
});

// 创建内存表
$table = new Swoole\Table(65536);
$table->column('id', swoole_table::TYPE_INT, 4);
$table->column('data', swoole_table::TYPE_STRING, 2048);
$table->create();

// 创建服务器表
$servers = new Swoole\Table(65536);
$servers->column('id', swoole_table::TYPE_INT, 4);
$servers->column('data', swoole_table::TYPE_STRING, 8192);
$servers->create();

// 创建 Socket TCP 服务器
$backend = $server->listen("0.0.0.0", SERVER_SOCK, SWOOLE_SOCK_TCP);
$backend->set(array(
	'worker_num' => WORKERS_NUM
));
$server->table = $table;
$server->servers = $servers;

// 有客户端连接
$backend->on('connect', function ($server, $fd){
	$fdinfo = $server->getClientInfo($fd);
    echo "Client {$fdinfo['remote_ip']} connect to server.\n";
});

// 接收到消息
$backend->on('receive', function ($server, $fd, $from_id, $data) {
	$json = json_decode($data, true);
	if($json) {
		if(isset($json['action'])) {
			switch($json['action']) {
				case "whitelist":
					// 判断传递过来的 Token 是否与开头生成的随机密码相同
					if(isset($json['token']) && $json['token'] == CLIENT_PASS) {
						// 判断是否提交了用户名和 IP 地址
						if(isset($json['username']) && isset($json['ip'])) {
							$server_id = $json['serverid'] ?? "";
							$server_data = json_decode($server->servers->get($server_id, 'data'), true);
							// 写入到内存表
							if($server_data) {
								$server_data['players'][$json['username']] = Array(
									'login' => true,
									'ip'    => $json['ip'],
									'time'  => time()
								);
								$server->servers->set($server_id, Array(
									'data' => json_encode($server_data)
								));
							} else {
								$server->table->set($json['username'], Array(
									'data' => json_encode(Array(
										'login' => true,
										'ip'    => $json['ip'],
										'time'  => time()
									))
								));
							}
							// 输出日志
							$server->send($fd, "Successful add player {$json['username']} to whitelist, server id: {$server_id}");
						} else {
							$server->send($fd, "Data undefined");
						}
					} else {
						$server->send($fd, "Client verify failed");
					}
					break;
				case "check":
					if(isset($json['username']) && isset($json['ip'])) {
						$data = $server->table->get($json['username'], 'data');
						if(!$data) {
							$server->send($fd, "You have not verified, please visit our website " . VERIFY_SITE . " to verify your account.");
						} else {
							$data = json_decode($data, true);
							if(!$data) {
								$server->send($fd, "You have not verified, please visit our website " . VERIFY_SITE . " to verify your account.");
							} else {
								if(isset($data['login']) && $data['login'] === true) {
									if(isset($data['time']) && time() - $data['time'] <= TIMEOUT_SET) {
										if(VERIFY_MCIP) {
											if(isset($data['ip']) && isset($json['ip']) && $data['ip'] == $json['ip']) {
												$data['time'] = time();
												$server->table->set($json['username'], Array("data" => json_encode($data)));
												$server->send($fd, "Authenticate successful");
											} else {
												$server->table->del($json['username']);
												$server->send($fd, "This ip address is not allow to connect this server.");
											}
										} else {
											$data['time'] = time();
											$server->table->set($json['username'], Array("data" => json_encode($data)));
											$server->send($fd, "Authenticate successful");
										}
									} else {
										$server->table->del($json['username']);
										$server->send($fd, "Login expired, please login again.");
									}
								} else {
									$server->table->del($json['username']);
									$server->send($fd, "Login expired, please login again.");
								}
							}
						}
					} else {
						$server->send($fd, "Data undefined");
					}
					break;
				case "register":
					$fdinfo = $server->getClientInfo($fd);
					$serverid = substr(md5($fdinfo['remote_ip'] . time()), 0, 7);
					$data = $server->servers->get($serverid, 'data');
					$server_name = $json['name'] ?? $fdinfo['remote_ip'];
					if($data) {
						echo "Server {$fdinfo['remote_ip']} already registed, skip...\n";
						$server->send($fd, $serverid);
					} else {
						$server->servers->set($serverid, Array(
							'data' => json_encode(Array(
								'registed' => true,
								'name'     => $server_name,
								'ip'       => $fdinfo['remote_ip'],
								'time'     => time(),
								'players'  => Array()
							))
						));
						echo "Server {$fdinfo['remote_ip']} successful registed, ID: {$serverid}\n";
						$server->send($fd, $serverid);
					}
					break;
				case "getinfo":
					if(isset($json['token']) && $json['token'] == CLIENT_PASS) {
						if(isset($json['serverid'])) {
							$data = $server->servers->get($json['serverid'], 'data');
							if($data) {
								$data = (isset($data) && $data !== "") ? $data : "false";
								$server->send($fd, $data);
							} else {
								$server->send($fd, "false");
							}
						} else {
							$server->send($fd, "false");
						}
					} else {
						$server->send($fd, "false");
					}
					break;
				case "getlist":
					if(isset($json['serverid']) || empty($json['serverid']) || $json['serverid'] == "null") {
						$data = $server->servers->get($json['serverid'], 'data');
						if($data) {
							$data = json_decode($data, true);
							$result = "";
							if($data) {
								$data['time'] = time();
								foreach($data['players'] as $key => $value) {
									$result .= "{$key};";
								}
								if($result !== "") {
									$result = substr($result, 0, strlen($result) - 1);
								}
								$result = $result == "" ? "false" : $result;
								$server->servers->set($json['serverid'], Array('data' => json_encode($data)));
								$server->send($fd, $result);
							} else {
								$server->send($fd, "unregister");
							}
						} else {
							$server->send($fd, "unregister");
						}
					} else {
						$server->send($fd, "unregister");
					}
					break;
				default:
					// Todo
			}
		} else {
			$server->send($fd, "Action undefined");
		}
	} else {
		echo "Client send: {$data}\n";
		$server->send($fd, "Bad Request");
	}
    $server->close($fd);
});

// 有客户端断开连接
$backend->on('close', function ($server, $fd) {
    echo "Client id {$fd} close the connection.\n";
});

// 启动服务器
$server->start();

// reCaptcha 验证
function recaptchaVerify($userdata) {
	$data = http_build_query(Array(
		'secret' => REC_SITEPWD,
		'response' => $userdata
	));
	$options = Array(
		'http' => Array(
			'method' => 'POST',
			'header' => 'Content-type:application/x-www-form-urlencoded',
			'content' => $data,
			'timeout' => 15 * 60
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents('https://recaptcha.net/recaptcha/api/siteverify', false, $context);
	$json = json_decode($result, true);
	return $json ? $json['success'] : false;
}

function getServerInfo($id) {
	$json = Json_Encode(Array(
		'token'     => CLIENT_PASS,
		'action'    => 'getinfo',
		'serverid'  => $id
	));
	$client = new swoole_client(SWOOLE_SOCK_TCP);
	if (!$client->connect('127.0.0.1', SERVER_SOCK, -1)) {
		$client->close();
		return false;
	} else {
		$client->send("{$json}\n");
		$data = json_decode($client->recv(), true);
		$client->close();
		if($data) {
			return $data;
		}
		return false;
	}
}
