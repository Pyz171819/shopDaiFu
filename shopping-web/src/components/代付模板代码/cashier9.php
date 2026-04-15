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

// 3. 状态判定
$is_paid = ($order['status'] == 1); 
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 900) - time(); 
$is_expired = ($remaining_seconds <= 0 && !$is_paid);

// === 核心：根据状态定义按钮和文案 ===
$btn_text = "确认支付";
$btn_url = "submit_pay.php?trade_no=".$trade_no."&tpl=cashier9";
$btn_class = ""; // 默认红底
$status_text = "订单未支付";

if ($is_paid) {
    $btn_text = "查看详情";
    $btn_url = "https://m.maoyan.com"; // 跳转猫眼
    $btn_class = "btn-disabled"; // 变灰
    $status_text = "订单已完成";
} elseif ($is_expired) {
    $btn_text = "查看详情";
    $btn_url = "https://m.maoyan.com"; // 跳转猫眼
    $btn_class = "btn-disabled"; // 变灰
    $status_text = "订单已过期";
}

// ============================================================
// 4. 【核心隐藏逻辑 - 严防死守版】
// ============================================================
$show_manager_tools = false; 
$from_param = isset($_GET['from']) ? $_GET['from'] : '';

// 只有：不是分享链接 && 没支付 && 没过期 && 是本人，才显示
if ($from_param !== 'share' && !$is_paid && !$is_expired) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
        $show_manager_tools = true;
    }
}
// ============================================================

// 5. 分享配置
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

$share_url = $base_url . '/cashier9.php?trade_no='.$trade_no.'&from=share';
$share_icon = $base_url . '/maoyan.png?v=' . time();
$share_title = "猫眼电影优惠券";
$share_desc = "精品电影，帮我付一下，我请你下次！";
$sc = get_share_card_config('cashier9');
if ($sc && $sc['share_title'] !== '') {
    $share_title = $sc['share_title'];
    $share_desc = $sc['share_desc'];
    if ((int)$sc['use_product_image'] === 0) {
        if ($sc['share_image'] !== '') {
            $share_icon = (strpos($sc['share_image'], 'http') === 0) ? $sc['share_image'] : $base_url . '/' . ltrim($sc['share_image'], '/');
        }
    } else {
        $share_icon = !empty($order['image']) ? ((strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/')) : ($base_url . '/maoyan.png');
    }
}

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>猫眼代付</title>
    
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; padding: 0; background-color: #F5F5F5; font-family: -apple-system, "PingFang SC", sans-serif; padding-bottom: 80px; }

        /* === 顶部倒计时卡片：重红晕 === */
        .heavy-red-card {
            background: #fff; border-radius: 12px; margin: 15px 12px; overflow: hidden;
            box-shadow: 0 5px 35px rgba(240, 61, 55, 0.4); 
            border: 1px solid rgba(240, 61, 55, 0.2);
        }

        /* === 商品信息卡片：淡红晕 === */
        .faint-red-card {
            background: #fff; border-radius: 12px; margin: 15px 12px; overflow: hidden;
            box-shadow: 0 0 20px rgba(240, 61, 55, 0.12); 
            border: 1px solid rgba(240, 61, 55, 0.05);
        }

        .timer-inner { padding: 12px 0; text-align: center; font-size: 13px; color: #555; font-weight: 500; }
        .timer-icon { margin-right: 6px; font-size: 14px; color: inherit; vertical-align: -1px; }
        #time-str { color: inherit; font-weight: 500; }

        .prod-content { display: flex; padding: 15px; padding-bottom: 0; }
        .p-img { width: 75px; height: 75px; border-radius: 6px; object-fit: cover; margin-right: 12px; flex-shrink: 0; border: 1px solid #f0f0f0; }
        .p-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .p-title { font-size: 16px; font-weight: bold; color: #333; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .p-desc { font-size: 12px; color: #999; margin-top: 4px; }
        .p-count { font-size: 12px; color: #999; margin-top: 2px; }

        .tag-row { display: flex; align-items: center; margin-top: 15px; font-size: 12px; padding: 12px 15px; border-top: 1px dashed #f5f5f5; }
        .tag-item { display: flex; align-items: center; margin-right: 15px; }
        .tag-item.red i { color: #F03D37; margin-right: 4px; font-size: 14px; }
        .tag-item.green i { color: #1BC57F; margin-right: 4px; font-size: 14px; }
        .tag-detail { margin-left: auto; color: #999; font-size: 12px; display: flex; align-items: center; }
        
        .normal-card { background: #fff; border-radius: 12px; margin: 12px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

        .card-title { font-size: 16px; font-weight: bold; margin-bottom: 12px; color: #333; }
        .list-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .list-label { color: #333; }
        .list-val { color: #999; }
        .sub-tips { font-size: 12px; color: #999; }
        .notice-list { font-size: 12px; color: #666; line-height: 1.8; padding-left: 0; list-style: none; margin: 0; }
        .notice-list li { margin-bottom: 4px; }

        .bottom-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -1px 10px rgba(0,0,0,0.05); z-index: 100; padding-bottom: max(10px, env(safe-area-inset-bottom)); }
        .total-price { color: #F03D37; font-size: 28px; font-weight: bold; font-family: DIN, sans-serif; }
        .total-price small { font-size: 16px; margin-right: 2px; }
        
        /* 状态文字颜色控制 */
        .status-text { font-size: 11px; color: #999; margin-top: -2px; }
        
        /* 按钮样式 */
        .btn-pay { background: #F03D37; color: #fff; font-size: 16px; font-weight: bold; border-radius: 22px; padding: 12px 35px; text-decoration: none; border: none; display: inline-block; }
        .btn-disabled { background: #CCC !important; cursor: pointer; } /* 允许点击跳转 */

        /* 右侧悬浮 */
        .floating-bar { position: fixed; right: 15px; bottom: 120px; z-index: 900; display: flex; flex-direction: column; gap: 15px; }
        .float-btn {
            width: 48px; height: 48px; background: #fff; border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            font-size: 10px; color: #666; cursor: pointer; transition: transform 0.1s;
        }
        .float-btn:active { transform: scale(0.9); }
        .float-btn i { font-size: 20px; color: #F03D37; margin-bottom: 1px; }

        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; text-align: right; padding: 20px; color: #fff; }
        
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; }
        #poster-img { width: 80%; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        
        #poster-source { 
            position: fixed; left: -9999px; top: 0; width: 375px; background: #fff; border-radius: 16px; overflow: hidden; 
            background-image: linear-gradient(to bottom, #ef4238 0%, #ef4238 60px, #fff 60px, #fff 100%);
        }
        .poster-header { height: 60px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: bold; }
        .poster-header i { font-size: 26px; margin-right: 8px; }
        .poster-content { padding: 25px; text-align: center; }
        .poster-price { color: #F03D37; font-size: 42px; font-weight: bold; margin-bottom: 20px; }
        .poster-prod { display: flex; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 8px; text-align: left; }
        .poster-prod img { width: 60px; height: 60px; border-radius: 4px; margin-right: 12px; object-fit: cover; }
        .poster-footer { padding: 20px; text-align: center; background: #f2f2f2; }
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

    <div class="heavy-red-card">
        <div class="timer-inner">
            <?php if($is_paid): ?>
                <i class="bi bi-check-circle-fill" style="color:#1BC57F"></i> 订单已完成支付
            <?php elseif($is_expired): ?>
                <i class="bi bi-x-circle-fill" style="color:#999"></i> 订单已关闭
            <?php else: ?>
                <i class="bi bi-clock-fill timer-icon"></i> 等待您的付款 <span id="time-str">00:15:00</span> 后订单自动关闭
            <?php endif; ?>
        </div>
    </div>

    <div class="faint-red-card">
        <div class="prod-content">
            <img src="<?php echo $order['image']; ?>" class="p-img">
            <div class="p-info">
                <div class="p-title"><?php echo htmlspecialchars($order['product_name']); ?></div>
                <div class="p-desc">【热卖限定包装】官方正品</div>
                <div class="p-count">1张</div>
            </div>
        </div>
        <div class="tag-row">
            <div class="tag-item red"><i class="bi bi-exclamation-circle-fill"></i> 不支持退票</div>
            <div class="tag-item green"><i class="bi bi-check-circle-fill"></i> 限时改签</div>
            <div class="tag-detail">查看详情 <i class="bi bi-chevron-right" style="font-size:10px"></i></div>
        </div>
    </div>

    <div class="normal-card">
        <div class="card-title">订单优惠</div>
        <div class="list-row"><span class="list-label">影票活动与优惠券</span><span class="list-val">无可用</span></div>
        <div class="list-row"><span class="list-label">猫享卡</span><span class="list-val">无可用</span></div>
        <div class="list-row"><span class="list-label">观影卡</span><span class="list-val">无可用</span></div>
    </div>
    <div class="normal-card">
        <div class="card-title">手机号</div>
        <div class="sub-tips">手机号仅用于生成订单,取票码不再以短信发送</div>
    </div>
    <div class="normal-card">
        <div class="card-title">购票须知</div>
        <ul class="notice-list">
            <li>1. 请提前30分钟左右到达影院现场，通过影院自助取票机完成取票。</li>
            <li>2. 若取票过程中遇到无法取票等其它问题，请联系影院工作人员进行处理。</li>
            <li>3. 请及时关注电影开场时间，凭票有序检票入场。</li>
        </ul>
    </div>

    <div class="bottom-bar">
        <div>
            <div class="total-price"><small>¥</small><?php echo $order['money']; ?></div>
            <div class="status-text" id="bottom-status-text"><?php echo $status_text; ?></div>
        </div>
        <a href="<?php echo $btn_url; ?>" class="btn-pay <?php echo $btn_class; ?>" id="bottom-btn"><?php echo $btn_text; ?></a>
    </div>

    <div class="mask" onclick="$(this).fadeOut();$('.share-guide').fadeOut();$('#poster-modal').fadeOut();"></div>
    <div class="share-guide" onclick="$('.mask').fadeOut();$(this).fadeOut();">
        <img src="curved-arrow.svg" style="width:60px; transform: rotate(-90deg); margin-right: 20px;">
        <p style="font-size:18px; font-weight:bold; margin-top:10px;">点击右上角菜单<br>发送给朋友</p>
    </div>

    <div id="poster-source">
        <div class="poster-header">
            <i class="bi bi-ticket-perforated-fill"></i> 猫眼电影优惠券
        </div>
        <div class="poster-content">
            <div class="poster-price"><small>¥</small><?php echo $order['money']; ?></div>
            <div class="poster-prod">
                <img src="<?php echo $order['image']; ?>" crossorigin="anonymous">
                <div style="flex:1; font-weight:bold; font-size:14px; line-height:1.4; color:#333;"><?php echo htmlspecialchars($order['product_name']); ?></div>
            </div>
        </div>
        <div class="poster-footer">
            <div id="qrcode-box" style="display:inline-block; padding:5px; background:#fff; border-radius:4px;"></div>
            <div style="font-size:12px; color:#666; margin-top:10px;">长按识别二维码帮TA付款</div>
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
                    useCORS: true, scale: 2, logging: false, backgroundColor: null 
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
                // 倒计时结束：更新状态为过期
                $('.timer-inner').html('<i class="bi bi-x-circle-fill"></i> 订单已关闭');
                
                // 更新底部文字
                $('#bottom-status-text').text('订单已过期');
                
                // 更新按钮状态
                $('#bottom-btn')
                    .addClass('btn-disabled')
                    .text('查看详情')
                    .attr('href', 'https://m.maoyan.com');
                
                // 页面重载以确保后端状态同步（可选，这里JS直接变UI体验更好）
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
            new QRCode(document.getElementById("qrcode-box"), { text: "<?php echo $share_url; ?>", width: 110, height: 110 });
            $('.floating-bar .float-btn').first().off('click').on('click', function(e){ e.preventDefault(); showGuide(); });
        });
    </script>
</body>
</html>