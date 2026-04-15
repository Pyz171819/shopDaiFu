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

// === 解析多选商品数据 ===
$order_items = [];
$is_multi_item = false;
if (!empty($order['items'])) {
    $decoded_items = json_decode($order['items'], true);
    if (is_array($decoded_items) && count($decoded_items) > 1) {
        $order_items = $decoded_items;
        $is_multi_item = true;
        $order['product_name'] = "打包购买 " . count($order_items) . " 件商品";
    }
}

// === 管理权限判断 ===
$show_manager_tools = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
    if (!isset($_GET['from']) || $_GET['from'] !== 'share') {
        $show_manager_tools = true;
    }
}

// ============================================================
// 【核心修改】固定分享文案与海报配置 (不再读取后台)
// ============================================================
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

// 1. 分享链接
$share_url = $base_url . '/cashier.php?trade_no='.$trade_no.'&from=share';

// 2. 分享信息（优先读后台卡片配置）
$share_title = "美团外卖";
$share_desc = "Hi~你和我的距离只差一顿外卖~";
$share_icon = $base_url . '/mt.png';
$sc = get_share_card_config('cashier');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/mt.png');
    }
}

// 3. 【修改点】固定海报背景 (大图)
$poster_bg = $base_url . '/mthbs.png'; 

// 4. 页面内部显示信息
$payer_nick = !empty($order['user_nick']) ? $order['user_nick'] : (get_config('payer_nick') ?: '须尽欢');
$payer_avatar = !empty($order['user_avatar']) ? $order['user_avatar'] : (get_config('payer_avatar') ?: 'https://img.yzcdn.cn/vant/cat.jpeg');
$payer_slogan = "Hi~你和我的距离只差一顿外卖~"; 

// 倒计时
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time();
if ($remaining_seconds < 0) $remaining_seconds = 0;

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>代付详情</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F5F5F5; font-family: -apple-system, sans-serif; }
        .user-header { padding: 20px 15px; display: flex; align-items: center; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #fff; }
        .user-info .nick { font-size: 16px; font-weight: bold; margin-bottom: 4px; color: #222; }
        .user-info .slogan { font-size: 12px; color: #666; }
        .card { background: #fff; border-radius: 12px; margin: 0 12px 12px; padding: 25px 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .pay-amount { text-align: center; font-size: 38px; font-weight: bold; margin-bottom: 5px; font-family: Arial, sans-serif; }
        .pay-amount::before { content: '¥'; font-size: 22px; margin-right: 4px; }
        .timer-wrap { display: flex; justify-content: center; align-items: center; margin-bottom: 30px; font-size: 14px; color: #333; }
        .timer-box { background: #333; color: #fff; padding: 2px 4px; border-radius: 4px; margin: 0 4px; font-weight: bold; }
        .notice-box { background: #FEF8E5; border-radius: 8px; padding: 15px; font-size: 12px; color: #8B572A; line-height: 1.8; margin-bottom: 15px; }
        .notice-title { color: #8B572A; margin-bottom: 5px; font-weight: bold; font-size: 13px; }
        .btn-main { display: block; width: 100%; background: #FDD934; color: #000; font-size: 16px; font-weight: bold; text-align: center; padding: 14px 0; border-radius: 25px; text-decoration: none; border: none; cursor: pointer; margin-top: 15px; }
        .btn-poster { background: #fff; border: 1px solid #FDD934; color: #F5A623; margin-top: 10px; } 
        .btn-card-share { background: #fff; border: 1px solid #07c160; color: #07c160; margin-top: 10px; }
        
        /* 商品列表样式 */
        .product-row { display: flex; align-items: center; margin-top: 15px; padding-bottom: 15px; border-bottom: 1px dashed #f5f5f5; } 
        .product-row:last-child { border-bottom: none; padding-bottom: 0; }
        .p-img { width: 60px; height: 60px; background: #f8f8f8; border-radius: 6px; margin-right: 12px; object-fit: cover; flex-shrink: 0; }
        .p-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .p-name { font-size: 14px; color: #333; line-height: 1.4; margin-bottom: 4px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        #poster-canvas { 
            position: absolute; top: 0; left: -9999px; 
            background-color: #fff;
            background-image: url('<?php echo $poster_bg; ?>');
            background-repeat: no-repeat;
            background-size: 100% 100%; 
            overflow: hidden; 
        }
        #poster-qr-container { position: absolute; bottom: 5%; right: 8%; z-index: 99; border: 4px solid #fff; border-radius: 4px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 10%; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .close-btn { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 35px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="user-header">
        <img src="<?php echo $payer_avatar; ?>" class="avatar">
        <div class="user-info">
            <div class="nick"><?php echo htmlspecialchars($payer_nick); ?></div>
            <div class="slogan"><?php echo htmlspecialchars($payer_slogan); ?></div>
        </div>
    </div>

    <div class="card">
        <?php if($order['status'] == 1): ?>
            <div style="text-align:center; padding: 10px 0;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <svg viewBox="0 0 1024 1024" width="26" height="26" style="margin-right: 10px;">
                        <path d="M512 0C229.23 0 0 229.23 0 512s229.23 512 512 512 512-229.23 512-512S794.77 0 512 0z" fill="#FFC300"/>
                        <path d="M426.67 746.67a32 32 0 0 1-22.61-9.39l-213.33-213.33a32 32 0 1 1 45.27-45.27L426.67 669.33l362.66-362.66a32 32 0 0 1 45.27 45.27l-385.33 385.33a32 32 0 0 1-22.6 9.4z" fill="#FFFFFF"/>
                    </svg>
                    <span style="font-size: 20px; font-weight: bold; color: #333;">美团用户已付款</span>
                </div>
                <div class="pay-amount"><?php echo $order['money']; ?></div>
                <div style="color: #888; font-size: 14px; margin-bottom: 20px;">微信支付</div>
            </div>
        <?php else: ?>
            <div style="text-align:center;">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #333;">需付款</div>
                <div class="pay-amount"><?php echo $order['money']; ?></div>
                <div class="timer-wrap">
                    支付剩余时间 <span class="timer-box" id="m">14</span> : <span class="timer-box" id="s">59</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="notice-box">
            <div class="notice-title">付款须知</div>
            1.代付订单创建成功后15分钟内未付款，订单会自动取消，你可以重新下单。<br>
            2.当代付订单退款成功后，实付金额将原路退还代付人。
        </div>

        <?php if($order['status'] == 1): ?>
            <a href="https://waimai.meituan.com/" class="btn-main">知道了</a>
        <?php else: ?>
            <?php if($remaining_seconds <= 0): ?>
                <button class="btn-main" style="background:#ccc; pointer-events:none;">订单已过期</button>
            <?php else: ?>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>" class="btn-main">为好友买单</a>
            <?php endif; ?>

            <?php if($show_manager_tools): ?>
                <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #eee;">
                    <button onclick="generatePoster()" class="btn-main btn-poster">生成海报分享</button>
                    <button onclick="alert('点击右上角分享卡片')" class="btn-main btn-card-share">微信卡片分享</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size: 14px; color: #666; font-weight: bold; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px; margin-bottom: 5px;">
            <?php echo htmlspecialchars($order['shop_name'] ?: '外卖订单'); ?>
        </div>

        <?php if ($is_multi_item): ?>
            <?php foreach($order_items as $item): ?>
            <div class="product-row">
                <img src="<?php echo $item['image']; ?>" class="p-img">
                <div class="p-info">
                    <div class="p-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:#999; font-size:12px;">X 1</span>
                        <span style="font-weight:bold; font-family:Arial;">¥ <?php echo $item['price']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; margin-top:10px; font-size:13px; color:#666;">
                共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#000;font-weight:bold;">¥<?php echo $order['money']; ?></span>
            </div>
        <?php else: ?>
            <div class="product-row" style="border-bottom:none; margin-top:10px;">
                <img src="<?php echo $order['image']; ?>" class="p-img">
                <div class="p-info">
                    <div class="p-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:#999; font-size:12px;">X 1</span>
                        <span style="font-weight:bold;">¥ <?php echo $order['price']; ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="poster-canvas">
        <div id="poster-qr-container"></div>
    </div>

    <div id="posterModal" class="modal">
        <div class="close-btn" onclick="$('#posterModal').fadeOut()">×</div>
        <img id="finalPoster" src="">
        <div style="color:#fff; margin-top:20px; font-size:14px;">长按图片保存，分享到朋友圈</div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
    
    <script>
        $(function(){
            <?php if($jsConfig): ?>
            wx.config({
                debug: false, appId: '<?php echo $jsConfig['appId']; ?>',
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
                    imgUrl: '<?php echo $share_icon; ?>'
                });
            });
            <?php endif; ?>
        });

        var rSeconds = <?php echo $remaining_seconds; ?>;
        function updateTimer() {
            if (rSeconds <= 0) return;
            var m = Math.floor(rSeconds / 60); var s = rSeconds % 60;
            if($('#m').length) { $('#m').text(m < 10 ? '0'+m : m); $('#s').text(s < 10 ? '0'+s : s); }
            rSeconds--;
        }
        setInterval(updateTimer, 1000); updateTimer();

        function generatePoster() {
            const btn = $('.btn-poster');
            btn.text('原图生成中...').prop('disabled', true);
            var img = new Image();
            img.crossOrigin = "Anonymous";
            img.onload = function() {
                var canvasBox = document.getElementById('poster-canvas');
                canvasBox.style.width = this.width + 'px';
                canvasBox.style.height = this.height + 'px';
                var qrSize = Math.floor(this.width * 0.25);
                $('#poster-qr-container').empty();
                new QRCode(document.getElementById("poster-qr-container"), {
                    text: "<?php echo $share_url; ?>",
                    width: qrSize,
                    height: qrSize,
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
                setTimeout(function(){
                    html2canvas(canvasBox, {
                        useCORS: true,
                        scale: 1,
                        allowTaint: false,
                        backgroundColor: null
                    }).then(canvas => {
                        $('#finalPoster').attr('src', canvas.toDataURL("image/png", 1.0));
                        $('#posterModal').fadeIn();
                        btn.text('生成海报分享').prop('disabled', false);
                    }).catch(err => {
                        alert('高清海报生成失败');
                        btn.prop('disabled', false);
                    });
                }, 800);
            };
            img.src = '<?php echo $poster_bg; ?>';
        }
    </script>
</body>
</html>