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
        $order['product_name'] = "拼多多合并订单 (共" . count($order_items) . "件)";
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
$share_url = $base_url . '/cashier4.php?trade_no='.$trade_no.'&from=share';

$nick_name = !empty($order['user_nick']) ? $order['user_nick'] : '朋友';
$share_title = $nick_name . '希望你帮他付' . $order['money'] . '元';
$share_desc = '我在拼多多上买到了很赞的东西，希望你帮我付款哦~';
$sc = get_share_card_config('cashier4');
if ($sc && $sc['share_desc'] !== '') { $share_desc = $sc['share_desc']; }
if ($sc && $sc['share_title'] !== '') { $share_title = $sc['share_title']; }

$share_icon_url = $base_url . '/uploads/pdd_icon.png';
if (!empty($order['image'])) {
    $share_icon_url = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
}
if ($sc && $sc['share_title'] !== '') {
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon_url = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon_url = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/uploads/pdd_icon.png');
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
    <title>拼多多代付</title>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #F4F4F4; 
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; 
        }

        /* 顶部红色背景 */
        .pdd-header {
            background-color: #F3554F; 
            padding: 20px 20px 60px 20px; 
            display: flex;
            align-items: flex-start;
        }

        .avatar-img { width: 50px; height: 50px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.2); }
        .user-col { display: flex; flex-direction: column; padding-top: 2px; }
        .nick-name { color: #fff; font-size: 16px; font-weight: 500; margin-bottom: 8px; }
        .bubble-msg { background-color: rgba(255, 255, 255, 0.2); color: #fff; font-size: 13px; padding: 6px 12px; border-radius: 4px; position: relative; line-height: 1.4; }
        .bubble-msg::before { content: ''; position: absolute; left: -6px; top: 50%; margin-top: -6px; border-width: 6px 6px 6px 0; border-style: solid; border-color: transparent rgba(255, 255, 255, 0.2) transparent transparent; }

        /* 主卡片 */
        .main-card {
            background: #fff;
            margin: -40px 12px 20px 12px; 
            border-radius: 8px;
            padding: 30px 20px 0 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            text-align: center;
        }

        .pay-label { font-size: 15px; color: #333; margin-bottom: 15px; font-weight: 500; }
        .price-wrap { color: #000; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 25px; display: flex; align-items: baseline; justify-content: center; }
        .symbol { font-size: 24px; margin-right: 4px; }
        .amount { font-size: 42px; letter-spacing: -1px; }

        .btn-pdd { display: block; width: 100%; background-color: #E02E24; color: #fff; font-size: 17px; font-weight: bold; padding: 12px 0; border-radius: 6px; text-decoration: none; border: none; margin-bottom: 15px; }
        .btn-pdd:active { opacity: 0.9; }
        .note-text { color: #9C9C9C; font-size: 12px; margin-bottom: 25px; transform: scale(0.95); }

        /* 商品区域 */
        .prod-section { border-top: 1px solid #F2F2F2; padding: 20px 0; text-align: left; }
        
        /* 多选适配样式 */
        .prod-item-row { display: flex; align-items: center; margin-bottom: 15px; }
        .prod-item-row:last-child { margin-bottom: 0; }
        
        .prod-img { width: 60px; height: 60px; border-radius: 4px; object-fit: cover; margin-right: 12px; flex-shrink: 0; background: #f8f8f8; }
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
        .prod-title { font-size: 14px; color: #151516; margin-bottom: 4px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .prod-spec { font-size: 12px; color: #9C9C9C; margin-bottom: 4px; }
        .prod-price-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #9C9C9C; }
        .price-small { color: #9C9C9C; font-family: Arial; }

        .manager-tools { margin: 20px 12px; text-align: center; }
        .btn-outline { display: inline-block; width: 48%; background: #fff; border: 1px solid #E02E24; color: #E02E24; padding: 10px 0; border-radius: 4px; font-size: 14px; text-decoration: none; }

        #poster-canvas { position: fixed; top: 0; left: -9999px; width: 375px; background: #f4f4f4; padding-bottom: 20px; z-index: 1; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; text-align: center; }
        .modal img { width: 85%; max-width: 320px; margin-top: 15%; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="pdd-header">
        <img src="<?php echo !empty($order['user_avatar']) ? $order['user_avatar'] : 'https://img.yzcdn.cn/vant/cat.jpeg'; ?>" class="avatar-img">
        <div class="user-col">
            <div class="nick-name"><?php echo !empty($order['user_nick']) ? $order['user_nick'] : '拼多多用户'; ?></div>
            <div class="bubble-msg">帮我付一下这件商品吧，谢谢啦</div>
        </div>
    </div>

    <div class="main-card">
        
        <div class="pay-label">代付金额</div>
        
        <div class="price-wrap">
            <span class="symbol">¥</span>
            <span class="amount"><?php echo $order['money']; ?></span>
        </div>

        <?php if($order['status'] == 0): ?>
            <?php if($remaining_seconds > 0): ?>
                <a href="submit_pay.php?trade_no=<?php echo $trade_no; ?>&tpl=cashier4" class="btn-pdd">立即支付</a>
            <?php else: ?>
                <button class="btn-pdd" style="background:#ccc; cursor:not-allowed;">订单已过期</button>
            <?php endif; ?>
        <?php else: ?>
            <a href="https://mobile.yangkeduo.com/" class="btn-pdd" style="background:#ccc; color:#fff; text-decoration:none;">来迟了，代付已付款</a>
        <?php endif; ?>

        <div class="note-text">如果订单申请退款，已支付金额将原路退还给您</div>

        <div class="prod-section">
            <?php if ($is_multi_item): ?>
                <?php foreach($order_items as $item): ?>
                <div class="prod-item-row">
                    <img src="<?php echo $item['image']; ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-price-row">
                            <span class="price-small">¥<?php echo $item['price']; ?></span>
                            <span>x1</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:right; border-top:1px dashed #eee; margin-top:15px; padding-top:10px; font-size:12px; color:#999;">
                    共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#E02E24;font-weight:bold;">¥<?php echo $order['money']; ?></span>
                </div>
            <?php else: ?>
                <div class="prod-item-row">
                    <img src="<?php echo !empty($order['image']) ? $order['image'] : 'https://via.placeholder.com/60'; ?>" class="prod-img">
                    <div class="prod-info">
                        <div class="prod-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-spec">默认规格</div>
                        <div class="prod-price-row">
                            <span class="price-small">¥<?php echo $order['price']; ?></span>
                            <span>x1</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if($show_manager_tools && $order['status'] == 0 && $remaining_seconds > 0): ?>
    <div class="manager-tools">
        <button onclick="generatePoster()" class="btn-outline" style="background: #E02E24; color: #fff;">生成海报</button>
        <button onclick="alert('请点击右上角【...】\n选择【发送给朋友】')" class="btn-outline">发送给好友</button>
    </div>
    <?php endif; ?>

    <div id="poster-canvas">
        <div class="pdd-header">
            <img src="<?php echo !empty($order['user_avatar']) ? $order['user_avatar'] : 'https://img.yzcdn.cn/vant/cat.jpeg'; ?>" class="avatar-img" crossorigin="anonymous">
            <div class="user-col">
                <div class="nick-name"><?php echo !empty($order['user_nick']) ? $order['user_nick'] : '拼多多用户'; ?></div>
                <div class="bubble-msg">帮我付一下这件商品吧，谢谢啦</div>
            </div>
        </div>
        <div class="main-card">
            <div class="pay-label">代付金额</div>
            <div class="price-wrap">
                <span class="symbol">¥</span><span class="amount"><?php echo $order['money']; ?></span>
            </div>
            <div style="text-align:center; padding-bottom:20px;">
                <div id="poster-qr" style="display:inline-block;"></div>
                <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码代付</div>
            </div>
            <div class="prod-section">
                <?php if ($is_multi_item): ?>
                    <?php foreach($order_items as $k => $item): if($k>=3) break; ?>
                    <div class="prod-item-row">
                        <img src="<?php echo $item['image']; ?>" class="prod-img" crossorigin="anonymous">
                        <div class="prod-info">
                            <div class="prod-title"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="prod-price-row">
                                <span class="price-small">¥<?php echo $item['price']; ?></span>
                                <span>x1</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($order_items)>3): ?>
                    <div style="text-align:center;font-size:11px;color:#999;">...等 <?php echo count($order_items); ?> 件商品</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="prod-item-row">
                        <img src="<?php echo !empty($order['image']) ? $order['image'] : 'https://via.placeholder.com/60'; ?>" class="prod-img" crossorigin="anonymous">
                        <div class="prod-info">
                            <div class="prod-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div class="prod-spec">默认规格</div>
                            <div class="prod-price-row">
                                <span class="price-small">¥<?php echo $order['price']; ?></span>
                                <span>x1</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
            new QRCode(document.getElementById("poster-qr"), { text: "<?php echo $share_url; ?>", width: 140, height: 140, colorDark : "#E02E24" });
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