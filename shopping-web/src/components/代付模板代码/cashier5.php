<?php
require 'core.php';

// 1. 获取订单号
$trade_no = !empty($_REQUEST['out_trade_no']) ? $_REQUEST['out_trade_no'] : (!empty($_REQUEST['trade_no']) ? $_REQUEST['trade_no'] : '');
$trade_no = strip_tags(trim($trade_no));

if(empty($trade_no)) die('错误：未能获取有效订单号。');

// 2. 查询订单详情
$sql = "SELECT o.*, p.name as product_name, p.image, p.price, p.shop_name, 
        u.nickname as user_nick, u.avatar as user_avatar 
        FROM orders o 
        LEFT JOIN products p ON o.product_id = p.id 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.out_trade_no = ?";
$stmt = $DB->prepare($sql);
$stmt->execute([$trade_no]);
$order = $stmt->fetch();

if (!$order) { echo "<h3>😭 订单不存在</h3>"; exit; }

// === 【核心修改】解析多选商品数据 ===
$order_items = [];
$is_multi_item = false;
if (!empty($order['items'])) {
    $decoded_items = json_decode($order['items'], true);
    if (is_array($decoded_items) && count($decoded_items) > 1) {
        $order_items = $decoded_items;
        $is_multi_item = true;
        // 覆盖标题，显示总件数
        $order['product_name'] = "淘宝极速代付 (共" . count($order_items) . "件商品)";
    }
}

// === 管理权限判断 ===
$show_manager_tools = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
    if (!isset($_GET['from']) || $_GET['from'] !== 'share') {
        $show_manager_tools = true;
    }
}

// 构造基础 URL
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'];
$share_url = $base_url . '/cashier5.php?trade_no='.$trade_no.'&from=share';

// 海报背景图
$poster_bg_url = $base_url . '/tbhb.png';

$nick_name = !empty($order['user_nick']) ? $order['user_nick'] : '朋友';
$share_title = '请你帮我付个款吧~';
$share_desc = '我正在使用淘宝闪购，请你帮我付个款吧~';
$share_icon_url = $base_url . '/tbtb.png';
$sc = get_share_card_config('cashier5');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon_url = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon_url = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/tbtb.png');
    }
} 

// 倒计时
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;
if ($order['status'] == 1) $remaining_seconds = 0; 

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>淘宝代付</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #f5f5f5; 
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; 
            padding-bottom: 80px;
        }

        /* 顶部橙色区域 */
        .tb-header {
            background-color: #FF5000;
            padding: 30px 20px 80px 20px; 
            text-align: center;
            color: #fff;
        }

        .avatar-box { position: relative; display: inline-block; margin-bottom: 10px; }
        .u-avatar { width: 60px; height: 60px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.5); object-fit: cover; background: #fff; }
        .slogan { font-size: 15px; font-weight: 500; opacity: 0.95; margin-top: 5px; position: relative; display: inline-block; padding-bottom: 15px; }
        .slogan::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 140%; height: 1px; background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

        /* 票据卡片容器 */
        .ticket-wrapper { margin: -50px 15px 15px 15px; position: relative; z-index: 10; }

        /* 上半部分 */
        .card-top { background: #fff; border-radius: 12px 12px 0 0; padding: 35px 20px 35px 20px; text-align: center; position: relative; box-shadow: 0 -2px 5px rgba(0,0,0,0.02); box-shadow: inset 0 4px 6px -4px rgba(0,0,0,0.15); }
        
        /* 分割线 */
        .tear-line { height: 16px; background: #fff; position: relative; margin: 0 10px; background-image: radial-gradient(circle at 0 8px, #f5f5f5 8px, transparent 8.5px), radial-gradient(circle at right 8px, #f5f5f5 8px, transparent 8.5px); background-size: 100% 100%; background-position: 0 0; background-repeat: no-repeat; }
        .tear-line::after { content: ''; position: absolute; top: 50%; left: 10px; right: 10px; height: 1px; border-top: 1px dashed #e0e0e0; transform: translateY(-50%); z-index: 5; }

        /* 下半部分 */
        .card-bottom { background: #fff; border-radius: 0 0 12px 12px; padding: 25px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }

        .price-label { font-size: 42px; color: #333; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 12px; }
        .price-symbol { font-size: 26px; margin-right: 2px; }
        .timer-text { font-size: 13px; color: #999; }
        .timer-num { color: #FF5000; font-weight: bold; margin: 0 2px; font-family: monospace; font-size: 14px; }
        .shop-name { font-size: 13px; color: #999; margin-bottom: 12px; }

        /* 商品展示样式 */
        .prod-row { display: flex; align-items: flex-start; }
        .prod-img { width: 68px; height: 68px; border-radius: 6px; object-fit: cover; margin-right: 14px; background: #f8f8f8; flex-shrink: 0; }
        .prod-info { flex: 1; }
        .prod-title { font-size: 14px; color: #333; font-weight: 500; line-height: 1.5; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prod-qty { font-size: 13px; color: #999; }

        /* 底部说明 */
        .footer-note { padding: 15px 30px; color: #999; font-size: 12px; line-height: 1.6; text-align: center; }

        /* 底部按钮 */
        .fixed-footer { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 10px 15px 25px 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.03); z-index: 100; }
        .btn-tb { display: block; width: 100%; background: linear-gradient(90deg, #FF9000 0%, #FF5000 100%); color: #fff; font-size: 17px; font-weight: bold; text-align: center; padding: 13px 0; border-radius: 25px; text-decoration: none; border: none; }
        .admin-btns { text-align: center; margin-top: 10px; }
        .btn-link { font-size: 12px; color: #999; text-decoration: none; margin: 0 10px; }

        /* 海报 */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 10%; border-radius: 10px; }
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; z-index: 1; overflow: hidden; }
        .poster-bg { width: 100%; display: block; }
        .poster-qr-box { position: absolute; bottom: 30px; right: 30px; background: #fff; padding: 5px; border-radius: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <div class="tb-header">
        <div class="avatar-box">
            <img src="<?php echo !empty($order['user_avatar']) ? $order['user_avatar'] : 'https://img.yzcdn.cn/vant/cat.jpeg'; ?>" class="u-avatar">
        </div>
        <br>
        <div class="slogan">万水千山总是情，帮我付款行不行</div>
    </div>

    <div class="ticket-wrapper">
        <div class="card-top">
            <div class="price-label">
                <span class="price-symbol">¥</span><?php echo $order['money']; ?>
            </div>
            
            <?php if($order['status'] == 1): ?>
                <div class="timer-text" style="color: #29C378; font-weight:bold;">
                    <i class="bi bi-check-circle-fill"></i> 支付已完成
                </div>
            <?php elseif($order['status'] == 0 && $remaining_seconds <= 0): ?>
                <div class="timer-text">订单已超时关闭</div>
            <?php else: ?>
                <div class="timer-text">
                    请在 <span class="timer-num" id="m">14</span> : <span class="timer-num" id="s">59</span> 内完成支付，超时将自动取消订单
                </div>
            <?php endif; ?>
        </div>

        <div class="tear-line"></div>

        <div class="card-bottom">
            <div class="shop-name"><?php echo htmlspecialchars($order['shop_name'] ?: '淘宝店铺'); ?></div>
            
            <?php if ($is_multi_item): ?>
                <?php foreach($order_items as $item): ?>
                <div class="prod-row" style="margin-bottom: 15px;">
                    <img src="<?php echo $item['image']; ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-qty" style="color:#FF5000; font-weight:bold;">¥<?php echo $item['price']; ?> <span style="color:#999;font-weight:normal;margin-left:5px;">x1</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; font-size:12px; color:#999; border-top:1px dashed #eee; padding-top:10px;">
                    共 <span style="color:#333;font-weight:bold;"><?php echo count($order_items); ?></span> 件，合计 <span style="color:#FF5000;font-weight:bold;">¥<?php echo $order['money']; ?></span>
                </div>
            <?php else: ?>
                <div class="prod-row">
                    <img src="<?php echo !empty($order['image']) ? $order['image'] : 'https://via.placeholder.com/60'; ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-qty">x1</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer-note">
        付款前请先与好友确认无误，避免资金受损<br>
        当代付订单申请退款成功后，实付金额将原路退还给代付人
    </div>

    <div class="fixed-footer">
        <?php if($order['status'] == 0): ?>
            <?php if($remaining_seconds > 0): ?>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier5" class="btn-tb">帮好友买单</a>
            <?php else: ?>
                <a href="https://taobao.com/" class="btn-tb" style="background:#ccc; text-decoration:none; color:#fff;">订单已过期 (去逛逛)</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="https://taobao.com/" class="btn-tb" style="background:#ccc; color:#fff; text-decoration:none;">来迟了，代付已付款</a>
        <?php endif; ?>

        <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
        <div class="admin-btns">
            <a href="javascript:;" class="btn-link" onclick="generatePoster()">生成海报</a>
            <a href="javascript:;" class="btn-link" onclick="alert('请点击右上角【...】\n选择【发送给朋友】')">发送给好友</a>
        </div>
        <?php endif; ?>
    </div>

    <div id="poster-canvas">
        <img src="<?php echo $poster_bg_url; ?>" class="poster-bg" crossorigin="anonymous">
        <div class="poster-qr-box">
            <div id="poster-qr"></div>
        </div>
    </div>

    <div id="posterModal" class="modal" onclick="$(this).fadeOut()">
        <img id="finalPoster" src="">
        <div style="color:#fff; margin-top:15px; font-size:14px;">长按图片保存到相册</div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
        // 倒计时
        var rSeconds = <?php echo $remaining_seconds; ?>;
        var isPaid = <?php echo $order['status']; ?>;
        function updateTimer() {
            if (isPaid == 1) return;
            if (rSeconds <= 0) return;
            var m = Math.floor(rSeconds / 60);
            var s = rSeconds % 60;
            $('#m').text(m < 10 ? '0'+m : m);
            $('#s').text(s < 10 ? '0'+s : s);
            rSeconds--;
        }
        setInterval(updateTimer, 1000); updateTimer();

        // 微信分享
        $(function(){
            <?php if($jsConfig): ?>
            wx.config({ debug: false, appId: '<?php echo $jsConfig['appId']; ?>', timestamp: <?php echo $jsConfig['timestamp']; ?>, nonceStr: '<?php echo $jsConfig['nonceStr']; ?>', signature: '<?php echo $jsConfig['signature']; ?>', jsApiList: ['updateAppMessageShareData'] });
            wx.ready(function () { 
                wx.updateAppMessageShareData({ 
                    title: '<?php echo $share_title; ?>', 
                    desc: '<?php echo $share_desc; ?>', 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $share_icon_url; ?>' 
                }); 
            });
            <?php endif; ?>
        });

        // 生成海报
        function generatePoster() {
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 80, height: 80, colorDark : "#000000" });
            
            setTimeout(function(){
                var c = document.getElementById('poster-canvas');
                c.style.left = '0'; c.style.zIndex = '-999';
                
                html2canvas(c, { 
                    scale: 2, 
                    useCORS: true, 
                    allowTaint: true 
                }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    c.style.left = '-9999px';
                });
            }, 800);
        }
    </script>
</body>
</html>