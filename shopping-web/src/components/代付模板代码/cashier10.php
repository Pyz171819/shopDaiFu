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

// === 【核心修改】解析多选商品数据 ===
$order_items = [];
$is_multi_item = false;
if (!empty($order['items'])) {
    $decoded_items = json_decode($order['items'], true);
    if (is_array($decoded_items) && count($decoded_items) > 1) {
        $order_items = $decoded_items;
        $is_multi_item = true;
        // 覆盖标题
        $order['product_name'] = "淘宝好物合并订单 (共" . count($order_items) . "件)";
    }
}

// 3. 状态判定 (15分钟过期)
$is_paid = ($order['status'] == 1); 
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time(); 
if ($remaining_seconds < 0) $remaining_seconds = 0;
$is_expired = ($remaining_seconds <= 0 && !$is_paid);

// === 按钮状态逻辑 ===
$btn_text = "慷慨付款";
$btn_url = "submit_pay.php?trade_no=".$trade_no."&tpl=cashier10";
$btn_class = ""; 

if ($is_paid) {
    $btn_text = "订单已完成";
    $btn_url = "https://m.taobao.com"; 
    $btn_class = "btn-disabled";
} elseif ($is_expired) {
    $btn_text = "订单已过期";
    $btn_url = "https://m.taobao.com"; 
    $btn_class = "btn-disabled";
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

$share_url = $base_url . '/cashier10.php?trade_no='.$trade_no.'&from=share';
$share_icon = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
$share_title = "淘宝好物分享";
$share_desc = "我在淘宝看中了这些宝贝，帮我付一下呗，下次请你吃饭！";
$sc = get_share_card_config('cashier10');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : (get_config('share_icon') ?: $base_url . '/tbtb.png');
        if (strpos($share_icon, 'http') !== 0 && $share_icon !== '') $share_icon = $base_url . '/' . ltrim($share_icon, '/');
    }
}

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>淘宝代付</title>
    
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #f1f1f1; 
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
            padding-bottom: 80px; 
        }

        /* 顶部导航 */
        .nav-bar { 
            background: #f1f1f1; 
            padding: 10px 15px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            height: 44px;
        }
        .nav-icon { font-size: 20px; color: #333; width: 30px; }
        .nav-right { text-align: right; }

        /* 价格区 */
        .price-section {
            text-align: center;
            padding: 30px 0 20px;
            color: #FF5000;
        }
        .price-symbol { font-size: 24px; font-weight: bold; margin-right: 2px; }
        .price-val { font-size: 46px; font-weight: bold; font-family: DIN, Arial, sans-serif; }

        /* 卡片 */
        .main-card {
            background: #fff;
            margin: 0 12px;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .help-tips { color: #999; font-size: 12px; display: flex; align-items: center; }
        .help-tips i { margin-left: 3px; font-size: 12px; }

        /* 商品布局 */
        .prod-row { display: flex; align-items: flex-start; }
        
        .p-img-box {
            position: relative;
            width: 85px;
            height: 85px;
            border-radius: 6px;
            overflow: hidden;
            margin-right: 12px;
            flex-shrink: 0;
            background: #f8f8f8;
        }
        .p-img { width: 100%; height: 100%; object-fit: cover; }
        .p-img-tag {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0,0,0,0.7);
            color: #fff;
            font-size: 10px; 
            text-align: center;
            padding: 2px 0;
            transform: scale(0.85); 
            width: 120%; 
            margin-left: -10%;
            white-space: nowrap;
        }

        .p-info { 
            flex: 1; 
            min-width: 0; 
            display: flex; 
            flex-direction: column; 
            justify-content: flex-start; 
            height: 85px; 
        }
        
        .p-title { 
            font-size: 14px; color: #333; line-height: 1.4; 
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        
        .tag-black {
            background: #000; color: #d4b178; 
            font-size: 10px; padding: 0 4px; border-radius: 2px;
            margin-right: 4px; vertical-align: 1px;
            font-weight: normal;
            display: inline-block;
            line-height: 14px;
        }

        .p-count-row {
            margin-top: 6px; 
            font-size: 14px;
            color: #999;
        }

        /* 底部按钮 */
        .bottom-area {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 10px 15px;
            padding-bottom: max(15px, env(safe-area-inset-bottom));
            background: transparent; 
            pointer-events: none; 
        }
        .btn-pay {
            display: block;
            width: 100%;
            height: 48px;
            line-height: 48px;
            text-align: center;
            background-image: linear-gradient(90deg, #FF9000 0%, #FF5000 100%);
            color: #fff;
            font-size: 17px;
            font-weight: bold;
            border-radius: 24px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(255, 80, 0, 0.2);
            pointer-events: auto; 
        }
        .btn-disabled { 
            background: #e0e0e0 !important; 
            background-image: none !important;
            color: #aaa !important; 
            box-shadow: none; 
        }

        /* 悬浮工具栏 */
        .floating-bar { position: fixed; right: 15px; bottom: 120px; z-index: 900; display: flex; flex-direction: column; gap: 15px; pointer-events: auto; }
        .float-btn {
            width: 48px; height: 48px; background: #fff; border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            font-size: 10px; color: #666; cursor: pointer; transition: transform 0.1s;
        }
        .float-btn:active { transform: scale(0.9); }
        .float-btn i { font-size: 20px; color: #FF5000; margin-bottom: 1px; }

        /* 遮罩与海报 */
        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; text-align: right; padding: 20px; color: #fff; }
        
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; }
        #poster-img { width: 80%; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        
        /* 海报源 */
        #poster-source { 
            position: fixed; left: -9999px; top: 0; width: 375px; background: #fff; border-radius: 16px; overflow: hidden; 
            padding-bottom: 20px;
        }
        .poster-top-bar { background: linear-gradient(90deg, #FF9000 0%, #FF5000 100%); height: 10px; }
        .poster-tb-logo { text-align: center; padding-top: 20px; font-weight: bold; font-size: 18px; color: #FF5000; display: flex; align-items: center; justify-content: center; }
        .poster-tb-logo i { margin-right: 6px; font-size: 22px; }
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

    <div class="nav-bar">
        
    </div>

    <div class="price-section">
        <span class="price-symbol">¥</span><span class="price-val"><?php echo $order['money']; ?></span>
    </div>

    <div class="main-card">
        <div class="card-header">
            <span>帮付订单信息</span>
            <span class="help-tips">帮我付说明 <i class="bi bi-question-circle"></i></span>
        </div>

        <?php if ($is_multi_item): ?>
            <?php foreach($order_items as $item): ?>
            <div class="prod-row" style="margin-bottom: 15px; border-bottom: 1px dashed #f9f9f9; padding-bottom: 10px;">
                <div class="p-img-box" style="width: 60px; height: 60px;">
                    <img src="<?php echo $item['image']; ?>" class="p-img">
                </div>
                <div class="p-info" style="height: auto; justify-content: center;">
                    <div class="p-title" style="-webkit-line-clamp: 1;">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </div>
                    <div class="p-count-row" style="margin-top: 4px;">
                        <span style="color:#FF5000;font-weight:bold;">¥<?php echo $item['price']; ?></span> 
                        <span style="color:#999;font-size:12px;margin-left:5px;">x1</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="text-align:right; font-size:12px; color:#999; padding-top:5px;">
                共 <?php echo count($order_items); ?> 件商品，合计 <span style="color:#333;font-weight:bold;">¥<?php echo $order['money']; ?></span>
            </div>
        <?php else: ?>
            <div class="prod-row">
                <div class="p-img-box">
                    <img src="<?php echo $order['image']; ?>" class="p-img">
                    <div class="p-img-tag">正品保真 | 现货秒发</div>
                </div>
                <div class="p-info">
                    <div class="p-title">
                        <span class="tag-black">次日达</span>
                        <?php echo htmlspecialchars($order['product_name']); ?>
                    </div>
                    <div class="p-count-row">x1</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="bottom-area">
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
        <div class="poster-top-bar"></div>
        <div class="poster-tb-logo">
            <i class="bi bi-cart-fill"></i> 淘宝代付
        </div>
        <div style="text-align:center; padding:20px 0 10px;">
            <div style="color:#FF5000; font-size:40px; font-weight:bold;"><small>¥</small><?php echo $order['money']; ?></div>
            <div style="color:#999; font-size:12px;">待支付金额</div>
        </div>
        
        <?php if ($is_multi_item): ?>
            <div style="background:#f9f9f9; margin:15px; padding:15px; border-radius:8px;">
                <?php foreach($order_items as $k => $item): if($k>=3) break; ?>
                <div style="display:flex; align-items:center; margin-bottom:10px;">
                    <img src="<?php echo $item['image']; ?>" style="width:40px; height:40px; border-radius:4px; margin-right:10px; object-fit:cover;" crossorigin="anonymous">
                    <div style="flex:1; font-size:12px; line-height:1.3; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(count($order_items)>3): ?>
                <div style="text-align:center; font-size:11px; color:#999;">...等 <?php echo count($order_items); ?> 件商品</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="background:#f9f9f9; margin:15px; padding:15px; border-radius:8px; display:flex; align-items:center;">
                <img src="<?php echo $order['image']; ?>" style="width:60px; height:60px; border-radius:4px; margin-right:10px; object-fit:cover;" crossorigin="anonymous">
                <div style="flex:1; font-size:13px; line-height:1.4; font-weight:bold; color:#333;">
                    <?php echo htmlspecialchars($order['product_name']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align:center; padding:10px;">
            <div id="qrcode-box" style="display:inline-block; padding:5px; background:#fff; border-radius:4px;"></div>
            <div style="font-size:11px; color:#999; margin-top:8px;">长按识别二维码帮TA付款</div>
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
                    useCORS: true, scale: 2, logging: false, backgroundColor: '#fff' 
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
            new QRCode(document.getElementById("qrcode-box"), { text: "<?php echo $share_url; ?>", width: 110, height: 110, colorDark : "#000000" });
            $('.floating-bar .float-btn').first().off('click').on('click', function(e){ e.preventDefault(); showGuide(); });
        });
    </script>
</body>
</html>