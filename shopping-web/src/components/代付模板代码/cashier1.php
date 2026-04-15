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
        $order['product_name'] = "京东合并订单 (共" . count($order_items) . "件)";
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

// 分享链接
$share_url = $base_url . '/cashier1.php?trade_no='.$trade_no.'&from=share';

// 分享标题/描述（优先读后台卡片配置）
$share_title = '我在京东挑了样好东西，请你帮我付款吧';
$share_desc = $order['product_name'] ?? '精选好物，帮忙代付一下~';
$sc = get_share_card_config('cashier1');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    if ($sc['share_desc'] !== '') $share_desc = $sc['share_desc'];
}

$product_img_raw = $order['image'];
$full_product_image = $product_img_raw;
if (!empty($product_img_raw) && strpos($product_img_raw, 'http') === false) {
    $full_product_image = $base_url . '/' . ltrim($product_img_raw, '/');
}
if (empty($full_product_image)) $full_product_image = get_config('share_icon');
if ($sc && $sc['share_title'] !== '') {
    if ((int)$sc['use_product_image'] === 0 && $sc['share_image'] !== '') {
        $full_product_image = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
    } else if ((int)$sc['use_product_image'] === 1) {
        $full_product_image = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : (get_config('share_icon') ?: $base_url . '/mt.png');
        if ($full_product_image && strpos($full_product_image, 'http') !== 0) $full_product_image = $base_url . '/' . ltrim($full_product_image, '/');
    }
}

// 用户信息
$payer_nick = !empty($order['user_nick']) ? $order['user_nick'] : (get_config('payer_nick') ?: '京东用户');
$payer_avatar = !empty($order['user_avatar']) ? $order['user_avatar'] : (get_config('payer_avatar') ?: 'https://img.yzcdn.cn/vant/cat.jpeg');

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
    <title>京东代付</title>
    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F6F6F6; font-family: -apple-system, Helvetica, sans-serif; padding-bottom: 80px; }
        
        .header-bg { position: relative; width: 100%; margin-bottom: -20px; z-index: 0; }
        .bg-img { width: 100%; display: block; height: auto; position: absolute; top: 0; left: 0; z-index: 1; }
        .user-interaction { position: relative; z-index: 100; display: flex; align-items: flex-start; padding: 20px 15px 40px 15px; }
        .u-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; margin-right: 12px; margin-top: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .bubble { flex: 1; background: transparent; border: none; box-shadow: none; padding: 20px 15px 20px 20px; position: relative; font-size: 14px; color: #333; line-height: 1.5; }
        .mascot { position: absolute; right: -5px; bottom: -15px; width: 75px; height: auto; z-index: 101; pointer-events: none; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.1)); }

        .jd-card { background: #fff; border-radius: 12px; margin: 0 12px 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative; overflow: hidden; z-index: 10; }
        
        .amount-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .amount-label { font-size: 16px; color: #333; font-weight: 500; }
        .receiver-info { font-size: 14px; color: #666; }
        
        .price-large { font-size: 36px; color: #F2270C; font-weight: bold; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; letter-spacing: -1px; }
        
        /* 计时器行 */
        .timer-row { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
        .timer-label { font-size: 13px; color: #999; }
        .timer-box { display: flex; align-items: center; font-size: 13px; color: #333; font-variant-numeric: tabular-nums; }
        .t-num { border: 1px solid #ddd; border-radius: 4px; padding: 1px 5px; margin: 0 3px; font-family: monospace; min-width: 24px; text-align: center; background: #fff; }

        .pay-method-title { font-size: 14px; color: #666; margin-bottom: 15px; }
        .pay-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; }
        .pay-left { display: flex; align-items: center; }
        .check-icon { color: #F2270C; font-size: 20px; }

        .btn-pay-now { display: block; width: 100%; background: linear-gradient(90deg, #ff3100 0%, #f2270c 100%); color: #fff; font-size: 17px; font-weight: bold; text-align: center; padding: 13px 0; border-radius: 25px; text-decoration: none; margin-top: 15px; box-shadow: 0 4px 12px rgba(242, 39, 12, 0.3); border: none; }

        .order-title { font-size: 14px; color: #333; margin-bottom: 15px; font-weight: bold; }
        .prod-row { display: flex; align-items: center; }
        .prod-img { width: 80px; height: 80px; border-radius: 6px; object-fit: cover; margin-right: 15px; border: 1px solid #f0f0f0; flex-shrink: 0; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 2px 0; min-width: 0; }
        .prod-name { font-size: 14px; color: #333; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .prod-price-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-top: 5px; }
        .prod-price { font-family: Arial; font-weight: bold; color: #F2270C; }
        .prod-qty { color: #999; font-size: 12px; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 80%; max-width: 300px; margin-top: 20%; border-radius: 10px; }
        
        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #f6f6f6; padding-bottom: 30px; z-index: 1; }

        .stamp-paid-img {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-60%) rotate(-15deg);
            width: 100px; 
            height: auto;
            z-index: 20;
            opacity: 0.9;
            pointer-events: none;
        }
        
        .stamp-expired {
            filter: grayscale(100%); 
            opacity: 0.6;
        }
    </style>
</head>
<body>

    <div class="header-bg">
        <img src="dingbu.png" class="bg-img" alt="bg">
        <div class="user-interaction">
            <img src="<?php echo $payer_avatar; ?>" class="u-avatar" crossorigin="anonymous">
            <div class="bubble">
                我在京东上挑好了商品，是时候该你仗义疏财啦，快帮我付个款吧~
                <img src="https://img14.360buyimg.com/imagetools/jfs/t1/138657/16/22818/5048/61d563a3E090967a5/4e09525287310023.png" 
                     class="mascot" 
                     crossorigin="anonymous"
                     onerror="this.style.display='none'">
            </div>
        </div>
    </div>

    <div class="jd-card">
        <div class="amount-row">
            <span class="amount-label" style="<?php echo $order['status']==1 ? 'color:#bbb;' : ''; ?>">代付金额</span>
            <span class="receiver-info">收货人：*<?php echo mb_substr($payer_nick, 1); ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
            
            <div class="price-large" style="line-height: 1; <?php echo $order['status']==1 ? 'color:#bbb; font-size:30px; font-weight:normal;' : ''; ?>">
                ¥<?php echo $order['money']; ?>
            </div>
            
            <div class="timer-row" style="<?php echo $order['status']==1 ? 'color:#bbb; gap:4px;' : 'gap:4px;'; ?>">
                <span class="timer-label" style="<?php echo $order['status']==1 ? 'color:#bbb;' : ''; ?>">剩余支付时间</span>
                <div class="timer-box" style="<?php echo $order['status']==1 ? 'color:#bbb;' : ''; ?>">
                    <span class="t-num" id="h"><?php echo $order['status']==1?'00':'00'; ?></span> : 
                    <span class="t-num" id="m"><?php echo $order['status']==1?'00':'14'; ?></span> : 
                    <span class="t-num" id="s"><?php echo $order['status']==1?'00':'59'; ?></span>
                </div>
            </div>
        </div>

        <?php if($order['status'] == 1): ?>
            <img src="yzf.png" class="stamp-paid-img" alt="已支付">
        <?php elseif ($order['status'] == 0 && $remaining_seconds <= 0): ?>
            <img src="guiqi.png" class="stamp-paid-img stamp-expired" alt="已过期">
        <?php endif; ?>
    </div>

    <?php if($order['status'] == 0): ?>
    <div class="jd-card">
        <div class="pay-method-title">支付方式</div>
        <div class="pay-row">
            <div class="pay-left">
                <i class="bi bi-wechat" style="color:#09BB07; font-size:22px; margin-right:8px;"></i>
                <span class="pay-name">微信支付</span>
            </div>
            <i class="bi bi-check-circle-fill check-icon"></i>
        </div>
        
        <?php if($remaining_seconds > 0): ?>
            <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier1" class="btn-pay-now">立即支付</a>
        <?php else: ?>
            <a href="javascript:;" class="btn-pay-now" style="background:#ccc; box-shadow:none;">订单已过期</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="jd-card">
        <div class="order-title">代付订单信息</div>
        
        <?php if ($is_multi_item): ?>
            <?php foreach($order_items as $item): ?>
            <div class="prod-row" style="margin-bottom: 12px; border-bottom: 1px dashed #f5f5f5; padding-bottom: 10px;">
                <img src="<?php echo $item['image']; ?>" class="prod-img" style="width:60px; height:60px;">
                <div class="prod-info">
                    <div class="prod-name" style="-webkit-line-clamp: 1;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="prod-price-row">
                        <span class="prod-price">¥<?php echo $item['price']; ?></span>
                        <span class="prod-qty">x1</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; font-size:12px; color:#999; padding-top:5px;">
                共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#F2270C;font-weight:bold;">¥<?php echo $order['money']; ?></span>
            </div>
        <?php else: ?>
            <div class="prod-row">
                <img src="<?php echo $full_product_image; ?>" class="prod-img" crossorigin="anonymous">
                <div class="prod-info">
                    <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="prod-price-row">
                        <span class="prod-price">¥<?php echo $order['price']; ?></span>
                        <span class="prod-qty">数量：1</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
    <div style="padding: 0 15px 20px;">
        <button onclick="generatePoster()" class="btn-pay-now" style="background:#fff; color:#F2270C; border:1px solid #F2270C; margin-top:0; margin-bottom:10px;">生成分享海报</button>
        <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" class="btn-pay-now" style="background:#fff; color:#09BB07; border:1px solid #09BB07; margin-top:0;">发送给好友</button>
    </div>
    <?php endif; ?>

    <div id="poster-canvas">
        <div class="header-bg">
            <img src="dingbu.png" class="bg-img" crossorigin="anonymous">
            <div class="user-interaction">
                <img src="<?php echo $payer_avatar; ?>" class="u-avatar" crossorigin="anonymous">
                <div class="bubble">
                    我在京东上挑好了商品，是时候该你仗义疏财啦，快帮我付个款吧~
                    <img src="https://img14.360buyimg.com/imagetools/jfs/t1/138657/16/22818/5048/61d563a3E090967a5/4e09525287310023.png" 
                         class="mascot" crossorigin="anonymous">
                </div>
            </div>
        </div>
        
        <div class="jd-card" style="margin-top:-10px; z-index:2;">
             <?php if ($is_multi_item): ?>
                <?php foreach($order_items as $k => $item): if($k>=3) break; ?>
                <div class="prod-row" style="margin-bottom:8px;">
                    <img src="<?php echo $item['image']; ?>" class="prod-img" style="width:40px; height:40px; margin-right:10px;" crossorigin="anonymous">
                    <div class="prod-info">
                        <div class="prod-name" style="font-size:12px; font-weight:bold; -webkit-line-clamp:1;"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="price-large" style="font-size:16px;">¥<?php echo $item['price']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(count($order_items)>3): ?><div style="font-size:11px;color:#999;text-align:center;">...等商品</div><?php endif; ?>
             <?php else: ?>
                <div class="prod-row">
                    <img src="<?php echo $full_product_image; ?>" class="prod-img" crossorigin="anonymous">
                    <div class="prod-info">
                        <div class="prod-name" style="font-size:14px; font-weight:bold;"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="price-large" style="font-size:24px; margin-top:10px;">¥<?php echo $order['money']; ?></div>
                    </div>
                </div>
             <?php endif; ?>
        </div>
        <div style="text-align:center; padding:20px; background:#fff; margin:12px; border-radius:12px;">
            <div id="poster-qr" style="display:inline-block;"></div>
            <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码代付</div>
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
                 if($('#m').text() != '00') location.reload();
                 return;
            }
            var h = Math.floor(rSeconds / 3600);
            var m = Math.floor((rSeconds % 3600) / 60);
            var s = rSeconds % 60;
            $('#h').text(h < 10 ? '0'+h : h);
            $('#m').text(m < 10 ? '0'+m : m);
            $('#s').text(s < 10 ? '0'+s : s);
            rSeconds--;
        }
        setInterval(updateTimer, 1000); updateTimer();

        $(function(){
            <?php if($jsConfig): ?>
            wx.config({ debug: false, appId: '<?php echo $jsConfig['appId']; ?>', timestamp: <?php echo $jsConfig['timestamp']; ?>, nonceStr: '<?php echo $jsConfig['nonceStr']; ?>', signature: '<?php echo $jsConfig['signature']; ?>', jsApiList: ['updateAppMessageShareData'] });
            wx.ready(function () { 
                wx.updateAppMessageShareData({ 
                    title: <?php echo json_encode($share_title); ?>, 
                    desc: <?php echo json_encode($share_desc); ?>, 
                    link: '<?php echo $share_url; ?>', 
                    imgUrl: '<?php echo $full_product_image; ?>' 
                }); 
            });
            <?php endif; ?>
        });

        function generatePoster() {
            var btn = event.target; $(btn).text('生成中...');
            $('#poster-qr').empty();
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140 });
            setTimeout(function(){
                var c = document.getElementById('poster-canvas');
                c.style.left = '0'; c.style.zIndex = '-999';
                html2canvas(c, { scale: 2, useCORS: true }).then(canvas => {
                    $('#finalPoster').attr('src', canvas.toDataURL("image/png"));
                    $('#posterModal').fadeIn();
                    c.style.left = '-9999px';
                    $(btn).text('生成分享海报');
                });
            }, 500);
        }
    </script>
</body>
</html>