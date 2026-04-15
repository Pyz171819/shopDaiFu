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
$share_url = $base_url . '/cashier7.php?trade_no='.$trade_no.'&from=share';

$share_title = '飞猪旅行特惠';
$share_desc = '发现了一个超棒的旅行套餐，帮我付一下，一起去玩吧！';
$share_icon_url = $base_url . '/feizhu.png';
$sc = get_share_card_config('cashier7');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon_url = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon_url = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/feizhu.png');
    }
} 

// 倒计时逻辑
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 1800) - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;
if ($order['status'] == 1) $remaining_seconds = 0; 

$jsConfig = get_wx_js_config();
$display_nick = htmlspecialchars($order['user_nick'] ?: '须尽欢');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>飞猪代付</title>
    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #F7F7F7; 
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; 
        }
        
        .fliggy-header {
            background: #fff; text-align: center; padding: 12px 0;
            position: relative; border-bottom: 1px solid #eee;
        }
        .header-title { font-size: 17px; color: #000; font-weight: 500; }
        .header-domain { font-size: 11px; color: #999; display: block; margin-top: 2px; }
        .close-icon { position: absolute; left: 15px; top: 12px; font-size: 24px; color: #333; }
        .more-icon { position: absolute; right: 15px; top: 12px; font-size: 24px; color: #333; }

        .container { padding: 15px; }

        /* 用户信息 */
        .user-info-row { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .avatar-img { width: 45px; height: 45px; border-radius: 4px; object-fit: cover; margin-right: 12px; flex-shrink: 0; }
        .user-content { flex: 1; display: flex; flex-direction: column; }
        .nick-line { 
            font-size: 15px; font-weight: bold; color: #333; 
            margin-bottom: 8px; line-height: 1; padding-top: 2px;
        }
        .nick-suffix { color: #333; font-weight: normal; margin-left: 2px; }

        /* 气泡框 */
        .chat-bubble { 
            background: #FFF1E0; border-radius: 4px; padding: 12px 15px; 
            position: relative; font-size: 14px; color: #666; line-height: 1.5;
        }
        .chat-bubble::before {
            content: ''; position: absolute; left: -8px; top: 10px;
            border-width: 5px 8px 5px 0; border-style: solid;
            border-color: transparent #FFF1E0 transparent transparent;
        }

        /* 橙色主卡片 */
        .orange-card {
            background: linear-gradient(90deg, #FF7E40 0%, #FF5A1E 100%);
            border-radius: 8px; padding: 22px 20px; color: #fff;
            position: relative; margin-bottom: 15px; overflow: hidden;
        }
        .card-header { display: flex; justify-content: space-between; font-size: 13px; opacity: 0.95; margin-bottom: 15px; }
        .price-row { margin-bottom: 25px; display: flex; align-items: baseline; }
        .price-symbol { font-size: 22px; margin-right: 2px; }
        .price-val { font-size: 38px; font-weight: bold; font-family: Arial; }
        .card-footer { font-size: 12px; opacity: 0.85; padding-top: 12px; border-top: 0.5px solid rgba(255,255,255,0.3); }
        .card-heart { position: absolute; right: -10px; bottom: -20px; font-size: 110px; opacity: 0.12; transform: rotate(15deg); pointer-events: none; }

        /* 商品带颜色框 */
        .product-box-colored {
            background: #FFF9E6; 
            border-radius: 8px; padding: 10px 12px; display: flex; align-items: center;
            margin-bottom: 25px; border: 0.5px solid #FFF2CC;
        }
        .product-img { 
            width: 45px; height: 45px; border-radius: 4px; object-fit: cover; 
            margin-right: 12px; background: #fff; flex-shrink: 0;
        }
        .product-name { font-size: 14px; color: #333; font-weight: 500; line-height: 1.4; flex: 1; }

        /* 按钮样式 */
        .btn-blue {
            display: block; width: 100%; background: #1677FF; color: #fff; text-align: center;
            padding: 14px 0; border-radius: 25px; font-size: 17px; font-weight: bold;
            text-decoration: none; border: none; margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2);
        }
        .btn-disabled { background: #ccc; box-shadow: none; cursor: not-allowed; }

        /* 说明文字 */
        .instr-section { font-size: 13px; color: #777; line-height: 1.8; }
        .instr-title { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .instr-item { margin-bottom: 5px; }
        .instr-orange { color: #FF5A1E; } 

        .provider-footer { text-align: center; font-size: 11px; color: #BBB; margin-top: 50px; padding-bottom: 30px; }
        
        /* 海报弹窗 */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 15%; border-radius: 12px; }
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #F7F7F7; padding-bottom: 30px; z-index: 1; }
    </style>
</head>
<body>

    

    <div class="container">
        <div class="user-info-row">
            <img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>" class="avatar-img">
            <div class="user-content">
                <div class="nick-line">
                    <?php echo $display_nick; ?><span class="nick-suffix">(**萌)</span>
                </div>
                <div class="chat-bubble">
                    我有一张订单需要支付，请帮我代付吧。非常感谢你~
                </div>
            </div>
        </div>

        <div class="orange-card">
            <div class="card-header">
                <span>帮我付订单信息</span>
                <span><?php echo ($order['status'] == 1) ? '订单已完成' : '订单未支付'; ?></span>
            </div>
            <div class="price-row">
                <span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span>
            </div>
            <div class="card-footer">实际金额以付款人确认付款时为准</div>
            <div class="card-heart">❤</div>
        </div>

        <div class="product-box-colored">
            <img src="<?php echo $order['image']; ?>" class="product-img">
            <div class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
        </div>

        <?php if($order['status'] == 0): ?>
            <?php if($remaining_seconds <= 0): ?>
                <a href="https://www.fliggy.com/" class="btn-blue btn-disabled">订单已过期 (点击返回)</a>
            <?php else: ?>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier7" class="btn-blue">帮他人付款</a>
            <?php endif; ?>
        <?php endif; ?>

        <div class="instr-section">
            <div class="instr-title">帮我付说明：</div>
            <div class="instr-item">1. 本产品正在为您提供帮亲友代付款的服务，您应在自愿法规定政策允许的范围内使用本产品。</div>
            <div class="instr-item">2. 付款前请务必确认认付方身份，以免造成诈骗行为。</div>
            <div class="instr-item">3. 选择[长期用TA付款]将持续获选为对方开通亲情付，完成开通后，您仍需通过当下页面继续完成当前付款行为。</div>
            <div class="instr-item instr-orange">4. 如果交易发生退款，已支付金额将原路退回付款人的付款账户。</div>
        </div>

        <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
        <div style="text-align:center; margin-top:25px; display:flex; justify-content: center; gap: 25px;">
             <button onclick="generatePoster()" style="background:none; border:none; color:#1677FF; font-size:15px; font-weight:500;">生成海报</button>
             <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" style="background:none; border:none; color:#1677FF; font-size:15px; font-weight:500;">微信卡片分享</button>
        </div>
        <?php endif; ?>
    </div>

    <div class="provider-footer">
        本服务由支付宝（中国）网络科技有限公司提供
    </div>

    <div id="poster-canvas">
        <div class="fliggy-header"><div class="header-title">飞猪代付</div></div>
        <div class="container" style="padding-top:20px;">
            <div class="user-info-row">
                <img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>" class="avatar-img" crossorigin="anonymous">
                <div class="user-content">
                    <div class="nick-line"><?php echo $display_nick; ?><span class="nick-suffix">(**萌)</span></div>
                    <div class="chat-bubble">我有一张订单需要支付，请帮我代付吧。非常感谢你~</div>
                </div>
            </div>
            <div class="orange-card">
                <div class="card-header">帮我付订单信息</div>
                <div class="price-row"><span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span></div>
                <div id="poster-qr" style="background:#fff; padding:10px; border-radius:8px; display:inline-block;"></div>
                <div style="font-size:12px; color:#fff; margin-top:10px; opacity:0.9;">长按识别二维码代付</div>
            </div>
            <div class="product-box-colored">
                <img src="<?php echo $order['image']; ?>" class="product-img" crossorigin="anonymous">
                <div class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
            </div>
        </div>
    </div>

    <div id="posterModal" class="modal" onclick="$(this).fadeOut()">
        <img id="finalPoster" src="">
        <div style="color:#fff; margin-top:15px; font-size:14px;">长按图片保存到相册并发送给好友</div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    <script>
        $(function(){
            <?php if($jsConfig): ?>
            wx.config({ debug: false, appId: '<?php echo $jsConfig['appId']; ?>', timestamp: <?php echo $jsConfig['timestamp']; ?>, nonceStr: '<?php echo $jsConfig['nonceStr']; ?>', signature: '<?php echo $jsConfig['signature']; ?>', jsApiList: ['updateAppMessageShareData', 'updateTimelineShareData'] });
            wx.ready(function () { 
                var shareData = { 
                    title: '<?php echo $share_title; ?>', 
                    desc: '<?php echo $share_desc; ?>', 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $share_icon_url; ?>' 
                };
                wx.updateAppMessageShareData(shareData);
                wx.updateTimelineShareData(shareData);
            });
            <?php endif; ?>
        });

        function generatePoster() {
            const btn = $(event.target); btn.text('生成中...');
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140 });
            setTimeout(function(){
                html2canvas(document.getElementById('poster-canvas'), { scale: 2, useCORS: true }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    btn.text('生成海报');
                });
            }, 500);
        }
    </script>
</body>
</html>