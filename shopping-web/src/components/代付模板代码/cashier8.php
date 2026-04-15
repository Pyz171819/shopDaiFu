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
        $order['product_name'] = "得物合并订单 (共" . count($order_items) . "件)";
    }
}

// 3. 核心状态判定
$is_paid = ($order['status'] == 1);
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time();
$is_expired = ($remaining_seconds <= 0 && !$is_paid);
$is_btn_grey = ($is_paid || $is_expired);

// === 分享权限逻辑 ===
$show_manager_tools = false;
$is_from_share = (isset($_GET['from']) && $_GET['from'] === 'share');

if (!$is_from_share && !$is_paid && !$is_expired) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
        $show_manager_tools = true;
    }
}

// 4. 数据脱敏与路径补全
$display_nick = $order['user_nick'] ? '*' . mb_substr($order['user_nick'], -1, 1, 'UTF-8') : '**试';
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'];

$share_url = $base_url . '/cashier8.php?trade_no=' . $trade_no . '&from=share';
$share_icon = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
$share_title = "得物限量版球鞋等你来";
$share_desc = "帮我付一下，这双鞋子太酷了，我等不及想要拥有它！";
$sc = get_share_card_config('cashier8');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/dewu_end.png');
    }
}

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>得物代付</title>
    
    <meta name="description" content="<?php echo $share_desc; ?>">
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F8F8F8; font-family: -apple-system, sans-serif; }

        /* 位置参数锁定 */
        .top-icons { position: absolute; top: 18px; left: 16px; z-index: 100; pointer-events: none; }
        .top-icons img { width: 75%; height: auto; display: block; }
        .header-bg { width: 100%; position: relative; z-index: 1; line-height: 0; }
        .header-bg img { width: 100%; height: auto; }
        .decoration-layer { width: 100%; position: relative; z-index: 2; margin-top: -25px; line-height: 0; }
        .decoration-layer img { width: 100%; height: auto; }
        .quote-overlay { position: absolute; top: 20px; left: 20px; right: 60px; color: #FFFFFF; font-size: 15px; font-weight: 600; line-height: 1.6; }

        /* 大白框对齐 */
        .unified-card { background: #fff; margin: -9.5px 12px 0 12px; border-radius: 4px; position: relative; z-index: 10; padding-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .price-section { padding: 40px 20px 25px; text-align: center; position: relative; }
        .price-seal { position: absolute; top: 10px; right: 15px; width: 75px; height: auto; z-index: 5; pointer-events: none; }
        .price-amount { font-size: 48px; font-weight: bold; color: #000; font-family: "Arial", sans-serif; }
        .timer-wrap { font-size: 14px; color: #999; display: flex; align-items: center; justify-content: center; margin-top: 15px; }
        .timer-box { border: 1px solid #E5E5E5; padding: 1px 4px; margin: 0 3px; color: #333; font-weight: bold; font-family: monospace; }
        
        .card-divider { margin: 10px 0 25px; border-top: 1px dashed #F2F2F2; }
        .product-section { padding: 0 20px; }
        .row-titles { display: flex; justify-content: space-between; align-items: center; font-size: 15px; color: #333; margin-bottom: 20px; }
        .row-titles span:nth-child(2) { text-align: right; flex: 1; }
        .receiver-tag { font-weight: bold; color: #000; }
        
        .product-item { display: flex; align-items: flex-start; margin-bottom: 25px; }
        .p-img { width: 85px; height: 85px; border-radius: 2px; object-fit: cover; margin-right: 15px; background: #fafafa; }
        .p-title { font-size: 15px; color: #333; font-weight: bold; line-height: 1.5; }

        /* 支付按钮 */
        .btn-pay-cyan { display: block; width: 100%; background-color: #00C2C9; color: #fff; font-size: 18px; font-weight: bold; text-align: center; padding: 15px 0; border-radius: 4px; text-decoration: none; margin-bottom: 20px; }
        .btn-disabled { background-color: #CCCCCC !important; box-shadow: none; cursor: default; }

        /* 弹窗组件 */
        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
        .share-sheet { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; z-index: 10000; border-radius: 12px 12px 0 0; padding: 20px 0; }
        .share-options { display: flex; justify-content: space-around; padding: 10px 0 20px; }
        .share-item { text-align: center; flex: 1; }
        .share-item i { font-size: 32px; display: block; margin-bottom: 8px; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85) url('curved-arrow.svg') no-repeat top right; background-size: 80px; z-index: 20000; text-align: center; color: #fff; padding-top: 150px; }
        
        #poster-source { position: fixed; top: 0; left: -9999px; width: 375px; background: #F8F8F8; }
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 30000; justify-content: center; align-items: center; flex-direction: column; }
        .poster-modal img { width: 85%; border-radius: 8px; }

        .serrated-edge { width: calc(100% - 24px); margin: 0 12px; line-height: 0; margin-top: -1px; }
        .serrated-edge img { width: 100%; height: auto; display: block; }
    </style>
</head>
<body>

    <div style="display:none; position:absolute;"><img src="<?php echo $share_icon; ?>"></div>

    <div id="main-content">
        <div class="top-icons"><img src="dw-top.png"></div>
        <div class="header-bg"><img src="dw-bg.png"></div>
        <div class="decoration-layer">
            <img src="dw-top-2.png">
            <div class="quote-overlay">“这款好物来自得物App，我超喜欢它 请你快来帮我付个款，谢啦！”</div>
        </div>

        <div class="unified-card">
            <div class="price-section">
                <?php if($is_paid): ?>
                    <img src="dewu_end.png" class="price-seal">
                <?php else: ?>
                    <img src="dwyz.png" class="price-seal" id="expiry-seal" style="<?php echo $is_expired ? 'display:block;' : 'display:none;'; ?>">
                <?php endif; ?>
                
                <div class="price-label" style="font-size:16px; color:#333; margin-bottom:12px;">付款金额</div>
                <div class="price-amount"><span style="font-size:28px; margin-right:2px;">¥</span><?php echo $order['money']; ?></div>
                <div class="timer-wrap">
                    <?php if($is_paid): ?>订单已完成支付<?php else: ?>剩余支付时间 <span class="timer-box" id="m">00</span> : <span class="timer-box" id="s">00</span><?php endif; ?>
                </div>
            </div>
            <div class="card-divider"></div>
            <div class="product-section">
                <div class="row-titles">
                    <span>付款商品</span>
                    <span>收货人：<span class="receiver-tag"><?php echo $display_nick; ?></span></span>
                </div>

                <?php if ($is_multi_item): ?>
                    <?php foreach($order_items as $item): ?>
                    <div class="product-item" style="margin-bottom: 15px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;">
                        <img src="<?php echo $item['image']; ?>" class="p-img" style="width:60px; height:60px;">
                        <div class="p-box" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                            <div class="p-title" style="font-size:14px; -webkit-line-clamp: 1; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="display:flex; justify-content:space-between; margin-top:5px; font-size:13px; color:#666;">
                                <span style="font-weight:bold; color:#000;">¥<?php echo $item['price']; ?></span>
                                <span>x1</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align:right; font-size:13px; color:#666; margin-bottom:15px;">
                        共 <?php echo count($order_items); ?> 件，合计 <span style="font-weight:bold; color:#000;">¥<?php echo $order['money']; ?></span>
                    </div>
                <?php else: ?>
                    <div class="product-item">
                        <img src="<?php echo $order['image']; ?>" class="p-img">
                        <div class="p-box"><div class="p-title"><?php echo htmlspecialchars($order['product_name']); ?></div></div>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo $is_btn_grey ? 'https://m.dewu.com/' : 'submit_pay.php?trade_no='.$trade_no.'&tpl=cashier8'; ?>" 
                   class="btn-pay-cyan <?php echo $is_btn_grey ? 'btn-disabled' : ''; ?>" 
                   id="pay-btn">豪爽支付</a>

                <?php if ($show_manager_tools): ?>
                <div id="share-entry-btn" style="text-align:center; padding:10px 0; color:#00C2C9; font-size:14px; font-weight:500;" onclick="showShareSheet()">
                    <i class="bi bi-share"></i> 分享给好友帮我付款
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="serrated-edge"><img src="dw-buttom.png"></div>
    </div>

    <div class="mask" onclick="hideShareSheet()"></div>
    <div class="share-sheet" id="shareSheet">
        <div class="share-options">
            <div class="share-item" onclick="showGuide()"><i class="bi bi-chat-dots" style="color:#07C160;"></i><span>卡片分享</span></div>
            <div class="share-item" onclick="generatePoster()"><i class="bi bi-image" style="color:#FF9800;"></i><span>生成海报</span></div>
        </div>
        <div style="text-align:center; padding:15px; border-top:1px solid #f5f5f5; color:#666;" onclick="hideShareSheet()">取消</div>
    </div>
    <div class="share-guide" id="shareGuide" onclick="$(this).fadeOut()">
        <p style="font-size:18px; font-weight:bold;">点击右上角菜单</p>
        <p>选择“发送给朋友”即可分享卡片</p>
    </div>

    <div id="poster-source">
        <div style="position: absolute; top: 30px; left: 0; width: 100%; text-align: center; color: #fff; font-size: 20px; font-weight: 600; z-index: 5;">得物代付</div>
        <div class="header-bg"><img src="dw-bg.png"></div>
        <div class="decoration-layer"><img src="dw-top-2.png"><div class="quote-overlay">发现好物，帮我付个款吧~</div></div>
        <div class="unified-card">
            <div class="price-section">
                <div class="price-amount"><span style="font-size:28px; margin-right:2px;">¥</span><?php echo $order['money']; ?></div>
                <div id="qrcode-box" style="margin: 20px auto; width: 140px; padding: 10px; border: 1px solid #eee;"></div>
                <div style="font-size: 13px; color: #999;">长按识别二维码帮TA付款</div>
            </div>
            <div class="card-divider"></div>
            <div class="product-section">
                <?php if ($is_multi_item): ?>
                    <?php foreach($order_items as $k => $item): if($k>=3) break; ?>
                    <div class="product-item" style="margin-bottom: 10px;">
                        <img src="<?php echo $item['image']; ?>" class="p-img" style="width:50px; height:50px;" crossorigin="anonymous">
                        <div class="p-box" style="flex:1;">
                            <div class="p-title" style="font-size:12px; -webkit-line-clamp: 1; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="font-size:12px; color:#000; font-weight:bold;">¥<?php echo $item['price']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($order_items)>3): ?><div style="text-align:center;font-size:12px;color:#999;margin-bottom:10px;">...等商品</div><?php endif; ?>
                <?php else: ?>
                    <div class="product-item">
                        <img src="<?php echo $order['image']; ?>" class="p-img" crossorigin="anonymous">
                        <div class="p-box"><div class="p-title" style="font-size:14px;"><?php echo htmlspecialchars($order['product_name']); ?></div></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="serrated-edge"><img src="dw-buttom.png"></div>
    </div>

    <div id="posterModal" class="poster-modal" onclick="$(this).fadeOut()">
        <img id="posterImg" src="">
        <div style="color:#fff; margin-top:15px; font-size:14px;">长按保存图片发送给好友</div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/qrcode.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>

    <script>
        // === 1. 核心交互函数 ===
        function showShareSheet() { $('.mask').fadeIn(); $('#shareSheet').slideDown(200); }
        function hideShareSheet() { $('.mask').fadeOut(); $('#shareSheet').slideUp(200); }
        function showGuide() { hideShareSheet(); $('#shareGuide').fadeIn(); }
        
        function generatePoster() {
            hideShareSheet();
            $('#qrcode-box').empty();
            new QRCode(document.getElementById("qrcode-box"), { text: "<?php echo $share_url; ?>", width: 140, height: 140 });
            setTimeout(() => {
                html2canvas(document.getElementById('poster-source'), { useCORS: true, scale: 2 }).then(canvas => {
                    $('#posterImg').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').css('display', 'flex').hide().fadeIn();
                });
            }, 500);
        }

        // === 2. 倒计时联动 ===
        var isPaid = <?php echo $is_paid ? 'true' : 'false'; ?>;
        var rSeconds = <?php echo $remaining_seconds; ?>;

        if (!isPaid) {
            var timer = setInterval(function() {
                if (rSeconds <= 0) {
                    clearInterval(timer);
                    $('#expiry-seal').fadeIn();
                    $('#pay-btn').addClass('btn-disabled').attr('href', 'https://m.dewu.com/');
                    $('#share-entry-btn').fadeOut();
                    return;
                }
                var m = Math.floor(rSeconds / 60); var s = rSeconds % 60;
                $('#m').text(m < 10 ? '0'+m : m); $('#s').text(s < 10 ? '0'+s : s);
                rSeconds--;
            }, 1000);
            if(rSeconds > 0) {
                var m = Math.floor(rSeconds / 60); var s = rSeconds % 60;
                $('#m').text(m < 10 ? '0'+m : m); $('#s').text(s < 10 ? '0'+s : s);
            }
        }

        // === 3. 微信分享初始化（必须带 jsApiList 右上角「发送给朋友」才生效）===
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
    </script>
</body>
</html>