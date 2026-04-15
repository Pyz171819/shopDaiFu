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
$share_url = $base_url . '/cashier2.php?trade_no='.$trade_no.'&from=share';

$share_icon_url = $base_url . '/xcfx.png';
$share_title = '携程旅行代付';
$share_desc = '我在携程下了一个订单，是时候该你仗义疏财啦，快帮我付款吧~';
$sc = get_share_card_config('cashier2');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon_url = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon_url = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/xcfx.png');
    }
}

// 图片绝对路径处理 (用于海报生成)
$product_img_raw = $order['image'];
$full_product_image = $product_img_raw;
if (!empty($product_img_raw) && strpos($product_img_raw, 'http') === false) {
    $full_product_image = $base_url . '/' . ltrim($product_img_raw, '/');
}
if (empty($full_product_image)) $full_product_image = get_config('share_icon');

// 信息处理
$product_name = htmlspecialchars($order['product_name']);
$slogan = '我在携程下了一个订单，是时候该你仗义疏财啦，快帮我付款吧~';

// 倒计时逻辑
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
    <title>携程代付</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F4F6F8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        
        /* 1. 顶部蓝色区域 */
        .ctrip-header {
            margin: 12px 12px 0 12px; 
            border-radius: 12px 12px 0 0;
            background: linear-gradient(180deg, #499BF7 0%, #2986F6 100%);
            padding: 25px 20px 80px 20px; 
            color: #fff;
            position: relative;
        }
        
        .header-content { display: flex; align-items: center; }
        
        .logo-img {
            width: 44px; height: 44px; border-radius: 50%; margin-right: 12px;
            background: #fff; padding: 8px; object-fit: contain; flex-shrink: 0;
        }
        
        .header-text-col { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .route-title { font-size: 15px; font-weight: bold; color: #fff; margin-bottom: 2px; line-height: 1.3; }
        .pay-msg { font-size: 13px; color: #fff; line-height: 1.4; font-weight: normal; opacity: 0.95; }

        /* 2. 主体卡片 */
        .main-card {
            background: #fff;
            margin: 0 12px;
            border-radius: 12px;
            position: relative;
            top: -60px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            overflow: hidden; 
            padding-bottom: 20px;
            z-index: 10;
        }

        .timer-bar {
            background: #FFF8F2; color: #FF7D00; font-size: 13px;
            padding: 14px 15px; text-align: center; font-weight: 500;
        }
        
        .pay-area { padding: 35px 20px 10px 20px; text-align: center; }
        .pay-label { color: #666; font-size: 15px; margin-bottom: 10px; }
        
        .pay-price {
            font-size: 42px; color: #333; font-weight: bold;
            font-family: Arial, sans-serif; margin-bottom: 35px; letter-spacing: -1px;
        }
        .pay-price::before { content: '¥'; font-size: 26px; margin-right: 4px; font-weight: normal; }

        /* 按钮样式 */
        .btn-ctrip {
            display: block; width: 100%;
            background: linear-gradient(90deg, #FFA500 0%, #FF7700 100%);
            color: #fff; font-size: 18px; font-weight: bold;
            text-align: center; padding: 14px 0; border-radius: 6px;
            text-decoration: none; border: none;
            box-shadow: 0 6px 15px rgba(255, 119, 0, 0.3);
            margin-bottom: 15px;
        }
        
        .btn-outline-blue {
            background: #fff; color: #0086F6; border: 1px solid #0086F6;
            font-size: 16px; box-shadow: none;
        }
        .btn-outline-orange {
            background: #fff; color: #FF7700; border: 1px solid #FF7700;
            font-size: 16px; box-shadow: none; margin-bottom: 0;
        }

        .info-box {
            background: #F7F8FA; border-radius: 8px; margin: 0 20px 20px 20px;
            padding: 18px; color: #888; font-size: 12px; line-height: 1.8;
        }
        .info-title { color: #333; font-weight: bold; font-size: 14px; margin-bottom: 6px; }

        .bottom-info-card {
            background: #fff; margin: -45px 12px 20px 12px; border-radius: 12px;
            padding: 15px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: relative; z-index: 5; color: #666; font-size: 13px;
        }

        /* 【修改】印章位置和大小
           right: 10px (更靠右)
           top: 50px (更靠上)
           width: 70px (更小)
        */
        .stamp-img {
            position: absolute; 
            right: 10px; 
            top: 50px; 
            width: 70px;
            opacity: 0.95; 
            transform: rotate(-15deg); 
            z-index: 20; 
            pointer-events: none;
        }
        .stamp-expired { filter: grayscale(100%); opacity: 0.6; }

        /* 海报相关 */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 15%; border-radius: 10px; }
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #f4f6f8; padding-bottom: 30px; z-index: 1; }
    </style>
</head>
<body>

    <div class="ctrip-header">
        <div class="header-content">
            <img src="xiec.png" class="logo-img" alt="Ctrip" crossorigin="anonymous">
            <div class="header-text-col">
                <div class="route-title"><?php echo $product_name; ?></div>
                <div class="pay-msg"><?php echo $slogan; ?></div>
            </div>
        </div>
    </div>

    <div class="main-card">
        <?php if($order['status'] == 0): ?>
            <div class="timer-bar">
                剩余支付时间：<span id="timer_display">00:14:59</span>，请尽快完成支付。
            </div>
        <?php else: ?>
            <div class="timer-bar" style="color:#28a745; background:#e8f5e9;">
                该订单已完成支付
            </div>
        <?php endif; ?>

        <div class="pay-area">
            <div class="pay-label">待付金额</div>
            <div class="pay-price"><?php echo $order['money']; ?></div>

            <?php if($order['status'] == 0): ?>
                <?php if($remaining_seconds > 0): ?>
                    <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier2" class="btn-ctrip">帮TA付款</a>
                <?php else: ?>
                    <button class="btn-ctrip" style="background:#ccc; box-shadow:none; cursor:not-allowed;">订单已过期</button>
                <?php endif; ?>
            <?php else: ?>
                <a href="https://m.ctrip.com" class="btn-ctrip" style="background:#ccc; box-shadow:none; text-decoration:none;">支付已完成</a>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <div class="info-title">代付说明</div>
            1. 付款前务必和好友再次确认，避免诈骗行为。<br>
            2. 如果发生退款，钱将退还到您的微信账户里。
        </div>

        <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
        <div style="padding: 0 20px;">
            <button onclick="generatePoster()" class="btn-ctrip btn-outline-blue">生成海报分享</button>
            <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" class="btn-ctrip btn-outline-orange">发送给好友</button>
        </div>
        <?php endif; ?>

        <?php if($order['status'] == 1): ?>
            <img src="yzf.png" class="stamp-img" alt="已支付">
        <?php elseif ($order['status'] == 0 && $remaining_seconds <= 0): ?>
            <img src="guiqi.png" class="stamp-img stamp-expired" alt="已过期">
        <?php endif; ?>
    </div>

    <div class="bottom-info-card">
        订单信息：<span style="font-family: monospace;"><?php echo $trade_no; ?></span>
    </div>

    <div id="poster-canvas">
        <div class="ctrip-header">
            <div class="header-content">
                <img src="xiec.png" class="logo-img" crossorigin="anonymous">
                <div class="header-text-col">
                    <div class="route-title"><?php echo $product_name; ?></div>
                    <div class="pay-msg"><?php echo $slogan; ?></div>
                </div>
            </div>
        </div>
        <div class="main-card">
            <div class="pay-area" style="padding-bottom: 20px;">
                <div class="pay-label">待付金额</div>
                <div class="pay-price"><?php echo $order['money']; ?></div>
            </div>
            <div style="text-align:center; padding: 20px;">
                <div id="poster-qr" style="display:inline-block;"></div>
                <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码代付</div>
            </div>
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
        var rSeconds = <?php echo $remaining_seconds; ?>;
        var isPaid = <?php echo $order['status']; ?>;

        function updateTimer() {
            if (isPaid == 1) return;
            if (rSeconds <= 0) {
                $('#timer_display').text("00:00:00"); return;
            }
            var h = Math.floor(rSeconds / 3600);
            var m = Math.floor((rSeconds % 3600) / 60);
            var s = rSeconds % 60;
            var hStr = h < 10 ? '0' + h : h;
            var mStr = m < 10 ? '0' + m : m;
            var sStr = s < 10 ? '0' + s : s;
            $('#timer_display').text(hStr + ":" + mStr + ":" + sStr);
            rSeconds--;
        }
        setInterval(updateTimer, 1000); 
        updateTimer(); 

        // 微信分享配置 (自定义标题描述+自定义图标 xcfx.png)
        $(function(){
            <?php if($jsConfig): ?>
            wx.config({ debug: false, appId: '<?php echo $jsConfig['appId']; ?>', timestamp: <?php echo $jsConfig['timestamp']; ?>, nonceStr: '<?php echo $jsConfig['nonceStr']; ?>', signature: '<?php echo $jsConfig['signature']; ?>', jsApiList: ['updateAppMessageShareData'] });
            wx.ready(function () { 
                wx.updateAppMessageShareData({ 
                    title: <?php echo json_encode($share_title); ?>, 
                    desc: <?php echo json_encode($share_desc); ?>, 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $share_icon_url; ?>' 
                }); 
            });
            <?php endif; ?>
        });

        // 海报生成
        function generatePoster() {
            var btn = event.target; $(btn).text('生成中...');
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140 });
            setTimeout(function(){
                var c = document.getElementById('poster-canvas');
                c.style.left = '0'; c.style.zIndex = '-999'; // 移入可视区但置底
                html2canvas(c, { scale: 2, useCORS: true }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    c.style.left = '-9999px'; // 恢复隐藏
                    $(btn).text('生成海报分享');
                });
            }, 500);
        }
    </script>
</body>
</html>