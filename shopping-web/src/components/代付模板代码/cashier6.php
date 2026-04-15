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
        // 覆盖标题
        $order['product_name'] = "携程旅行合并订单 (共" . count($order_items) . "件)";
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
$share_url = $base_url . '/cashier6.php?trade_no='.$trade_no.'&from=share';

$share_title = '携程特惠酒店等你来';
$share_desc = '亲爱的朋友，帮我完成这趟旅程吧~';
$share_icon_url = $base_url . '/xcfx.png';
$sc = get_share_card_config('cashier6');
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

// 倒计时逻辑 (30分钟)
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 1800) - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;
if ($order['status'] == 1) $remaining_seconds = 0; 

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>携程酒店</title>
    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #f1f3f5; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif; }
        
        /* 顶部绿色区域 */
        .ctrip-header {
            background-color: #2dbb9a;
            height: 180px;
            padding: 15px;
            color: #fff;
            text-align: center;
            position: relative;
        }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .header-title { font-size: 18px; font-weight: bold; }
        .header-domain { font-size: 12px; opacity: 0.8; }
        .header-nav { font-size: 20px; font-weight: bold; margin-top: 15px; }
        
        /* 主体卡片 */
        .main-card {
            background: #fff;
            margin: -60px 15px 20px;
            border-radius: 12px;
            position: relative;
            z-index: 10;
            padding-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        /* 头像 */
        .avatar-wrap {
            width: 70px; height: 70px;
            margin: -35px auto 10px;
            background: #fff;
            padding: 3px;
            border-radius: 50%;
            overflow: hidden;
        }
        .avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        
        .invite-title { text-align: center; font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .invite-desc { text-align: center; font-size: 13px; color: #666; margin-bottom: 20px; }
        
        /* 商品信息 */
        .prod-box { display: flex; padding: 0 20px 20px; }
        .prod-img { width: 85px; height: 85px; border-radius: 8px; object-fit: cover; margin-right: 15px; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: space-around; }
        .prod-name { font-size: 16px; font-weight: bold; color: #333; }
        .prod-detail { font-size: 13px; color: #888; }

        /* 锯齿分割线 */
        .divider {
            height: 1px; border-top: 1px dashed #eee;
            margin: 0 5px; position: relative;
        }
        .divider::before, .divider::after {
            content: ''; position: absolute; top: -8px;
            width: 16px; height: 16px; background: #f1f3f5; border-radius: 50%;
        }
        .divider::before { left: -15px; }
        .divider::after { right: -15px; }
        
        /* 支付区域 */
        .pay-area { text-align: center; padding: 25px 20px; }
        .price-row { font-size: 16px; color: #333; display: flex; align-items: baseline; justify-content: center; margin-bottom: 15px; }
        .price-tag { margin-right: 5px; }
        .price-symbol { color: #f85e13; font-weight: bold; font-size: 18px; margin-right: 3px; }
        .price-val { color: #f85e13; font-weight: bold; font-size: 32px; font-family: Arial; }
        
        .timer-row { font-size: 13px; color: #666; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .timer-box { background: #ff6b3d; color: #fff; padding: 1px 4px; border-radius: 2px; margin: 0 3px; font-family: monospace; }
        
        .btn-pay {
            display: block; width: 100%;
            background-color: #2dbb9a; color: #fff;
            font-size: 16px; font-weight: bold;
            text-align: center; padding: 12px 0; border-radius: 25px;
            text-decoration: none; border: none;
            box-shadow: 0 4px 10px rgba(45, 187, 154, 0.2);
        }
        
        /* 说明文字 */
        .notice-section { padding: 0 20px; color: #888; font-size: 12px; line-height: 1.8; }
        .notice-title { font-size: 13px; font-weight: bold; color: #666; margin-bottom: 8px; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 15%; border-radius: 10px; }
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #f1f3f5; padding-bottom: 30px; z-index: 1; }
    </style>
</head>
<body>

    <div class="ctrip-header">
        <div class="header-top">
            
        </div>
        <div class="header-nav">
            <i class="bi bi-house-door-fill" style="float: left; font-size: 18px;"></i>
            他人代付
        </div>
    </div>

    <div class="main-card">
        <div class="avatar-wrap">
            <img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>">
        </div>
        
        <div class="invite-title"><?php echo htmlspecialchars($order['user_nick'] ?: '须尽欢'); ?> 发起的代付邀请</div>
        <div class="invite-desc">我选好了商品 你来买单吧~</div>

        <?php if ($is_multi_item): ?>
            <div style="padding: 0 20px 20px;">
                <?php foreach($order_items as $item): ?>
                <div style="display:flex; margin-bottom:15px; border-bottom:1px dashed #f5f5f5; padding-bottom:10px;">
                    <img src="<?php echo $item['image']; ?>" style="width:60px; height:60px; border-radius:4px; object-fit:cover; margin-right:10px; flex-shrink:0;">
                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                        <div style="font-size:14px; font-weight:bold; color:#333; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block;">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div style="font-size:13px; color:#f85e13; font-weight:bold;">¥<?php echo $item['price']; ?> <span style="color:#999;font-weight:normal;font-size:12px;">x1</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; font-size:12px; color:#666;">
                    共 <?php echo count($order_items); ?> 件，总额 <span style="color:#f85e13;font-weight:bold;">¥<?php echo $order['money']; ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="prod-box">
                <img src="<?php echo $order['image']; ?>" class="prod-img">
                <div class="prod-info">
                    <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="prod-detail">1天 x1</div>
                    <div class="prod-detail"><?php echo htmlspecialchars($order['shop_name'] ?: '携程精选'); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="pay-area">
            <div class="price-row">
                <span class="price-tag">
                    <?php 
                    if($order['status'] == 1) { echo "已支付"; }
                    elseif($remaining_seconds <= 0) { echo "已过期"; }
                    else { echo "待支付"; }
                    ?>
                </span>
                <span class="price-symbol">¥</span>
                <span class="price-val"><?php echo $order['money']; ?></span>
            </div>
            
            <?php if($order['status'] == 1): ?>
                <a href="https://www.ctrip.com/" class="btn-pay" style="background:#ccc; box-shadow:none;">帮ta付款</a>
                <div style="color:#2dbb9a; font-weight:bold; margin-top:10px;"><i class="bi bi-check-circle-fill"></i> 代付已完成</div>
            <?php elseif($remaining_seconds <= 0): ?>
                <a href="https://www.ctrip.com/" class="btn-pay" style="background:#ccc; box-shadow:none;">帮ta付款</a>
                <div style="color:#999; margin-top:10px;">订单已关闭</div>
            <?php else: ?>
                <div class="timer-row">
                    支付倒计时 
                    <span class="timer-box" id="m">14</span> : 
                    <span class="timer-box" id="s">59</span>
                </div>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier6" class="btn-pay">帮ta付款</a>
            <?php endif; ?>
        </div>

        <div class="notice-section">
            <div class="notice-title">代付说明</div>
            1. 代付订单创建后30分钟内未付款，订单会自动取消，你可以重新下单。<br>
            2. 当代付订单退款成功后，实付金额将原路退还代付人。
        </div>

        <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
        <div style="padding: 20px 20px 0; text-align:center;">
             <button onclick="generatePoster()" class="btn-pay" style="background:#fff; color:#2dbb9a; border:1px solid #2dbb9a; box-shadow:none; font-size:14px;">生成分享海报</button>
             <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" class="btn-pay" style="background:#fff; color:#f85e13; border:1px solid #f85e13; box-shadow:none; font-size:14px; margin-top:10px;">微信卡片分享</button>
        </div>
        <?php endif; ?>
    </div>

    <div id="poster-canvas">
        <div class="ctrip-header" style="height: 150px; padding-top: 40px; text-align: center;">
            <div style="font-size: 22px; font-weight: bold; margin-bottom: 8px;">携程酒店</div>
            <div style="font-size: 15px; opacity: 0.9;">他人代付邀请</div>
        </div>
        <div class="main-card" style="margin-top: -50px;">
            <div class="avatar-wrap"><img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>" crossorigin="anonymous"></div>
            <div class="invite-title"><?php echo htmlspecialchars($order['user_nick']); ?></div>
            <div class="pay-area">
                <div class="price-row"><span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span></div>
                
                <?php if ($is_multi_item): ?>
                    <div style="text-align:left; padding:10px; background:#f9f9f9; margin-bottom:10px; border-radius:4px;">
                        <?php foreach($order_items as $k => $item): if($k>=3) break; ?>
                        <div style="font-size:12px; color:#333; margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            • <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if(count($order_items)>3): ?><div style="font-size:11px;color:#999;text-align:center;">...等商品</div><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="font-size:14px; color:#333; margin-bottom:10px;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <?php endif; ?>

                <div id="poster-qr" style="margin: 0 auto; width: 140px;"></div>
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
        // 微信分享配置
        $(function(){
            <?php if($jsConfig): ?>
            wx.config({ 
                debug: false, 
                appId: '<?php echo $jsConfig['appId']; ?>', 
                timestamp: <?php echo $jsConfig['timestamp']; ?>, 
                nonceStr: '<?php echo $jsConfig['nonceStr']; ?>', 
                signature: '<?php echo $jsConfig['signature']; ?>', 
                jsApiList: ['updateAppMessageShareData', 'updateTimelineShareData'] 
            });
            wx.ready(function () { 
                wx.updateAppMessageShareData({ 
                    title: '<?php echo $share_title; ?>', 
                    desc: '<?php echo $share_desc; ?>', 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $share_icon_url; ?>' 
                });
                wx.updateTimelineShareData({ 
                    title: '<?php echo $share_title; ?>', 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $share_icon_url; ?>' 
                });
            });
            <?php endif; ?>
        });

        var rSeconds = <?php echo $remaining_seconds; ?>;
        function updateTimer() {
            if (rSeconds <= 0) return;
            var m = Math.floor(rSeconds / 60); var s = rSeconds % 60;
            $('#m').text(m < 10 ? '0'+m : m); $('#s').text(s < 10 ? '0'+s : s);
            rSeconds--;
        }
        setInterval(updateTimer, 1000); updateTimer();

        function generatePoster() {
            const btn = $(event.target); btn.text('生成中...');
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140 });
            setTimeout(function(){
                var c = document.getElementById('poster-canvas');
                c.style.left = '0'; c.style.zIndex = '-999';
                html2canvas(c, { scale: 2, useCORS: true }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    c.style.left = '-9999px';
                    btn.text('生成分享海报');
                });
            }, 500);
        }
    </script>
</body>
</html>