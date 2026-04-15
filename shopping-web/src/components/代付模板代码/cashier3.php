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
$share_url = $base_url . '/cashier3.php?trade_no='.$trade_no.'&from=share';

$share_title = '滴滴快车优惠券';
$share_desc = '亲爱的，帮我付个车费，让我平安到达目的地~';
$share_icon_url = $base_url . '/didi.png';
$sc = get_share_card_config('cashier3');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon_url = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon_url = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/didi.png');
    }
}

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
    <title>滴滴代付</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #F3F4F5; /* 滴滴背景灰 */
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; 
        }
        
        /* 顶部标题栏 */
        .didi-header {
            background: #fff;
            text-align: center;
            padding: 12px 0;
            font-size: 17px;
            font-weight: 500;
            color: #000;
            border-bottom: 1px solid #eee;
        }
        .didi-header span { font-size: 12px; color: #999; display:block; margin-top:2px; font-weight: normal; }

        /* 主卡片 */
        .main-card {
            background: #fff;
            margin: 12px;
            border-radius: 12px;
            padding: 40px 20px 30px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
        }

        /* 需付款文字 */
        .pay-label {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }

        /* 金额 (细、小) */
        .pay-price {
            font-size: 40px; 
            color: #000;
            font-weight: 500; 
            font-family: Arial, sans-serif;
            margin-bottom: 40px;
            letter-spacing: -0.5px;
        }
        .pay-price span {
            font-size: 18px;
            font-weight: normal;
            margin-left: 2px;
        }

        /* 提示框 */
        .notice-box {
            background: #F7F8FA;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            margin-bottom: 30px;
        }
        .notice-title {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }
        .notice-item {
            font-size: 13px;
            color: #999;
            line-height: 1.6;
            margin-bottom: 8px;
            display: flex;
        }
        .notice-num { margin-right: 4px; }

        /* 按钮 (滴滴绿) */
        .btn-didi {
            display: block;
            width: 100%;
            background: #29C378; /* 滴滴标志性绿色 */
            color: #fff;
            font-size: 17px;
            font-weight: bold;
            text-align: center;
            padding: 14px 0;
            border-radius: 25px; /* 胶囊圆角 */
            text-decoration: none;
            border: none;
            box-shadow: 0 4px 10px rgba(41, 195, 120, 0.2);
        }
        .btn-didi:active { opacity: 0.9; }

        /* 底部信息 */
        .footer-info {
            text-align: center;
            font-size: 12px;
            color: #ccc;
            margin-top: 20px;
        }
        
        /* 管理员工具 */
        .manager-tools { margin-top: 20px; padding-top: 20px; border-top: 1px dashed #eee; }
        .btn-outline {
            background: #fff; border: 1px solid #29C378; color: #29C378;
            font-size: 14px; padding: 10px; border-radius: 20px; width: 48%;
            display: inline-block; box-shadow: none;
        }

        /* 海报弹窗 */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 15%; border-radius: 10px; }
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #fff; padding-bottom: 30px; z-index: 1; }
    </style>
</head>
<body>

    

    <div class="main-card">
        
        <?php if($order['status'] == 0 && $remaining_seconds <= 0): ?>
            <div class="pay-label" style="color:#999">订单已失效</div>
        <?php else: ?>
            <div class="pay-label">需付款</div>
        <?php endif; ?>

        <div class="pay-price">
            <?php echo $order['money']; ?><span>元</span>
        </div>

        <div class="notice-box">
            <div class="notice-title">请您知悉：</div>
            <div class="notice-item">
                <span class="notice-num">1.</span>
                <span>您支付的订单，是亲友使用滴滴出行打车时发生的金额，详细账单以您亲友的滴滴出行或小程序订单明细为准</span>
            </div>
            <div class="notice-item">
                <span class="notice-num">2.</span>
                <span>若亲友选择了滴滴平台发放的各类优惠，则本金额为抵扣过亲友优惠的金额</span>
            </div>
            <div class="notice-item">
                <span class="notice-num">3.</span>
                <span>如发生退款，实付金额将原路退还给代付人</span>
            </div>
        </div>

        <?php if($order['status'] == 0): ?>
            <?php if($remaining_seconds > 0): ?>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier3" class="btn-didi">为好友买单</a>
            <?php else: ?>
                <button class="btn-didi" style="background:#ccc; box-shadow:none; cursor:not-allowed;">订单已过期</button>
            <?php endif; ?>
        <?php else: ?>
            <a href="https://www.didiglobal.com/" class="btn-didi" style="background:#ccc; color:#fff; box-shadow:none; text-decoration:none;">来迟了，代付已付款</a>
        <?php endif; ?>

        <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
        <div class="manager-tools">
            <button onclick="generatePoster()" class="btn-didi btn-outline">生成海报</button>
            <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" class="btn-didi btn-outline">发送给好友</button>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer-info">
        滴滴出行 · 安全支付
    </div>

    <div id="poster-canvas">
        <div class="didi-header">滴滴代付</div>
        <div class="main-card" style="box-shadow:none; border:1px solid #eee;">
            <div class="pay-label"><?php echo $share_title; ?></div>
            <div class="pay-price"><?php echo $order['money']; ?><span>元</span></div>
            <div class="notice-box">
                <div class="notice-title">请您知悉：</div>
                <div class="notice-item"><?php echo $share_desc; ?></div>
            </div>
            <div style="text-align:center; padding-top:10px;">
                <div id="poster-qr" style="display:inline-block;"></div>
                <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码为好友买单</div>
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

        function generatePoster() {
            var btn = event.target; $(btn).text('生成中...');
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140, colorDark : "#29C378" });
            setTimeout(function(){
                var c = document.getElementById('poster-canvas');
                c.style.left = '0'; c.style.zIndex = '-999';
                html2canvas(c, { scale: 2, useCORS: true }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    c.style.left = '-9999px';
                    $(btn).text('生成海报');
                });
            }, 500);
        }
    </script>
</body>
</html>