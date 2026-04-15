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

// 3. 状态判定 (30分钟过期)
$is_paid = ($order['status'] == 1); 
$created_time = strtotime($order['create_time']);
$remaining_seconds = ($created_time + 1800) - time(); 
if ($remaining_seconds < 0) $remaining_seconds = 0;
$is_expired = ($remaining_seconds <= 0 && !$is_paid);

// === 按钮与状态文案 ===
$btn_price_text = "¥" . $order['money'];
$btn_label_text = "为好友买单";
$btn_url = "submit_pay.php?trade_no=".$trade_no."&tpl=cashier13";
$btn_class = ""; 

if ($is_paid) {
    $btn_price_text = "已卖出";
    $btn_label_text = "交易完成";
    $btn_url = "javascript:;"; 
    $btn_class = "btn-disabled";
} elseif ($is_expired) {
    $btn_price_text = "已失效";
    $btn_label_text = "宝贝不存在";
    $btn_url = "javascript:;"; 
    $btn_class = "btn-disabled";
}

// 模拟原价
$original_price = number_format($order['money'] * 1.5, 2);

// ============================================================
// 4. 分享配置 (强制修正版)
// ============================================================
$protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

$share_url = $base_url . '/cashier13.php?trade_no='.$trade_no.'&from=share';
$share_icon = (strpos($order['image'], 'http') === 0) ? $order['image'] : $base_url . '/' . ltrim($order['image'], '/');
$share_title = "帮我看看闲鱼宝贝";
$share_desc = "成色很好，物超所值！快帮我付款吧，这个价格真的很划算";
$sc = get_share_card_config('cashier13');
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

// 只有：不是分享链接 && 没支付 && 没过期 && 是本人，才显示悬浮工具
$show_manager_tools = false; 
$from_param = isset($_GET['from']) ? $_GET['from'] : '';
if ($from_param !== 'share' && !$is_paid && !$is_expired) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id']) {
        $show_manager_tools = true;
    }
}

$jsConfig = get_wx_js_config();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo $share_title; ?></title>
    
    <meta property="og:title" content="<?php echo $share_title; ?>">
    <meta property="og:description" content="<?php echo $share_desc; ?>">
    <meta property="og:image" content="<?php echo $share_icon; ?>">

    <link rel="stylesheet" href="css/bootstrap-icons/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            margin: 0; padding: 0; 
            background-color: #fff; 
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Helvetica Neue", Arial, sans-serif;
            padding-bottom: 80px; 
            color: #111;
        }

        /* === 顶部用户信息栏 === */
        .header {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #fff;
        }
        .u-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 1px solid #f0f0f0;
        }
        .u-info { 
            flex: 1; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .u-left-group { display: flex; align-items: center; }
        .u-name { font-size: 15px; font-weight: bold; color: #111; margin-right: 6px; }
        .u-tag { 
            background: #E6F1FC; color: #108EE9; 
            font-size: 10px; padding: 1px 4px; border-radius: 2px; font-weight: bold; 
            display: flex; align-items: center; white-space: nowrap;
        }
        .u-tag img { width: 10px; margin-right: 2px; }
        .u-status { font-size: 11px; color: #999; text-align: right; white-space: nowrap; }

        /* === 价格区域 === */
        .price-section {
            padding: 5px 15px;
            display: flex;
            align-items: flex-end; 
        }
        .p-symbol { font-size: 18px; color: #FF2B2B; font-weight: bold; margin-bottom: 3px;}
        .p-val { font-size: 32px; color: #FF2B2B; font-weight: bold; font-family: DIN, sans-serif; line-height: 1; }
        .p-badge-red {
            color: #FF2B2B; font-size: 11px; background: rgba(255, 43, 43, 0.08);
            padding: 2px 5px; border-radius: 4px; margin-left: 8px; margin-bottom: 4px;
        }
        
        .p-sub-row { padding: 0 15px; margin-top: 5px; }
        .p-sub-tag {
            border: 1px solid #FF2B2B; color: #FF2B2B; font-size: 10px;
            padding: 0 2px; border-radius: 2px; display: inline-block;
        }

        .p-stats-row {
            padding: 0 15px;
            margin-top: 8px;
            color: #999;
            font-size: 12px;
            display: flex;
            align-items: center;
        }
        .text-del { text-decoration: line-through; margin-left: 2px; margin-right: 2px;}

        /* === 内容区域 === */
        .content { padding: 10px 15px 0; }
        
        /* 交易须知 */
        .safety-tip {
            margin-bottom: 15px;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .st-title { 
            font-weight: bold; 
            display: flex; 
            align-items: center; 
            margin-bottom: 4px;
        }
        .st-title i { margin-left: 4px; color: #999; font-size: 13px; }
        .st-desc { color: #999; display: block; }

        /* 商品描述 (靠左) */
        .c-text {
            font-size: 16px; 
            line-height: 1.6; 
            color: #000; 
            font-weight: 500;
            margin-bottom: 20px;
            margin-top: 0; 
            white-space: pre-wrap; 
            text-align: left; 
            display: block;
        }

        /* 商品大图 */
        .prod-img-box {
            padding: 0 15px;
            margin-bottom: 20px;
        }
        .prod-img {
            width: 100%;
            border-radius: 12px;
            display: block;
        }

        /* === 悬浮工具栏 === */
        .floating-bar { 
            position: fixed; 
            right: 15px; 
            bottom: 120px; 
            z-index: 900; 
            display: flex; 
            flex-direction: column; 
            gap: 12px; 
        }
        .float-btn {
            width: 48px; 
            height: 48px; 
            background: #fff; 
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center;
            font-size: 10px; 
            color: #666; 
            cursor: pointer; 
            transition: transform 0.1s;
        }
        .float-btn:active { transform: scale(0.9); }
        .float-btn i { font-size: 20px; color: #FFDA44; margin-bottom: 1px; } 

        /* === 底部操作栏 === */
        .bottom-bar {
            position: fixed;
            bottom: 0; left: 0; width: 100%;
            background: #fff;
            display: flex; align-items: center;
            padding: 8px 10px;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            box-shadow: 0 -1px 5px rgba(0,0,0,0.05);
            z-index: 100;
        }
        
        .b-icon-grp {
            display: flex;
            margin-right: 10px;
        }
        .b-icon-item {
            width: 48px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-size: 10px; color: #333;
            cursor: pointer;
        }
        .b-icon-item:active { opacity: 0.7; }
        .b-icon-item i { font-size: 20px; margin-bottom: 2px; }
        
        .b-btns {
            flex: 1;
            display: flex;
            gap: 10px;
        }
        
        .btn-chat {
            width: 80px;
            height: 44px;
            background: #F6F7F9; 
            border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            color: #111; font-weight: bold; font-size: 14px;
            cursor: pointer;
        }
        .btn-chat:active { background: #eee; }

        .btn-pay-friend {
            flex: 1;
            height: 44px;
            background: #FFDA44;
            border-radius: 22px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #111; 
            text-decoration: none;
            line-height: 1.1;
        }
        .btn-pay-friend:active { opacity: 0.9; }
        .btn-pay-price { font-size: 16px; font-weight: 900; font-family: DIN, sans-serif; }
        .btn-pay-label { font-size: 11px; font-weight: 500; }
        
        .btn-disabled { background: #eee !important; color: #999 !important; pointer-events: none; }

        /* === 留言弹窗 === */
        .msg-modal {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .msg-box {
            background: #fff; width: 85%; padding: 20px; border-radius: 12px;
            text-align: center;
        }
        .msg-textarea {
            width: 100%; height: 80px; border: 1px solid #eee; background: #f9f9f9;
            border-radius: 8px; padding: 10px; font-size: 14px; resize: none; margin-bottom: 15px;
        }
        .msg-btn {
            width: 100%; padding: 10px; background: #FFDA44; border: none; border-radius: 20px;
            font-weight: bold; font-size: 15px; color: #111;
        }

        /* 遮罩与海报 */
        .mask { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; }
        .share-guide { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; text-align: right; padding: 20px; color: #fff; }
        
        .poster-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; }
        #poster-img { width: 80%; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        
        #poster-source { 
            position: fixed; left: -9999px; top: 0; width: 375px; background: #fff; border-radius: 0; overflow: hidden; 
            padding-bottom: 30px;
        }
        /* 海报右侧Logo */
        .poster-logo-right {
            margin-left: auto;
            display: flex; align-items: center;
        }
        .xy-logo-img { width: 60px; height: auto; display: block;}
    </style>
</head>
<body>

    <?php if ($show_manager_tools): ?>
    <div class="floating-bar">
        <div class="float-btn" onclick="showGuide()">
            <i class="bi bi-share-fill"></i>
            卡片
        </div>
        <div class="float-btn" onclick="generatePoster()">
            <i class="bi bi-images"></i>
            海报
        </div>
    </div>
    <?php endif; ?>

    <div class="header">
        <img src="<?php echo $order['user_avatar'] ?: 'siji.png'; ?>" class="u-avatar">
        <div class="u-info">
            <div class="u-left-group">
                <div class="u-name"><?php echo htmlspecialchars($order['shop_name'] ?: '卖家'); ?></div>
                <div class="u-tag">鱼小铺 L1</div>
            </div>
            <div class="u-status">1小时前来过 | 济南</div>
        </div>
    </div>

    <div class="price-section">
        <span class="p-symbol">¥</span>
        <span class="p-val"><?php echo $order['money']; ?></span>
        <span class="p-badge-red">2人小刀价</span>
    </div>
    
    <div class="p-sub-row">
        <span class="p-sub-tag">包邮</span>
    </div>

    <div class="p-stats-row">
        直接买 <span class="text-del">¥<?php echo $original_price; ?></span> | 2人想要 | 128人浏览
    </div>

    <div class="content">
        <div class="safety-tip">
            <div class="st-title">
                闲鱼交易须知 <i class="bi bi-info-circle-fill"></i> 
            </div>
            <div class="st-desc">买前了解退货规则，保障你的交易权益</div>
        </div>

        <div class="c-text"><?php echo htmlspecialchars($order['product_name']); ?></div>
    </div>

    <div class="prod-img-box">
        <img src="<?php echo $order['image']; ?>" class="prod-img">
    </div>

    <div class="bottom-bar">
        <div class="b-icon-grp">
            <div class="b-icon-item" onclick="openMsgModal()">
                <i class="bi bi-chat-dots"></i>
                留言
            </div>
            <div class="b-icon-item" onclick="alert('收藏成功')">
                <i class="bi bi-star"></i>
                收藏
            </div>
        </div>
        
        <div class="b-btns">
            <div class="btn-chat" onclick="alert('非买家账号不能聊天')">
                聊一聊
            </div>
            
            <a href="<?php echo $btn_url; ?>" class="btn-pay-friend <?php echo $btn_class; ?>">
                <div class="btn-pay-price"><?php echo $btn_price_text; ?></div>
                <div class="btn-pay-label"><?php echo $btn_label_text; ?></div>
            </a>
        </div>
    </div>

    <div class="msg-modal" id="msgModal" onclick="if(event.target==this) closeMsgModal()">
        <div class="msg-box">
            <div style="font-weight:bold; margin-bottom:10px;">给卖家留言</div>
            <textarea class="msg-textarea" id="msgInput" placeholder="想对卖家说点什么..."></textarea>
            <button class="msg-btn" onclick="sendMsg()">发送</button>
        </div>
    </div>

    <div class="mask" onclick="$(this).fadeOut();$('.share-guide').fadeOut();$('#poster-modal').fadeOut();"></div>
    <div class="share-guide" onclick="$('.mask').fadeOut();$(this).fadeOut();">
        <img src="curved-arrow.svg" style="width:60px; transform: rotate(-90deg); margin-right: 20px;">
        <p style="font-size:18px; font-weight:bold; margin-top:10px;">点击右上角菜单<br>发送给朋友</p>
    </div>

    <div id="poster-source">
        <div class="header">
            <img src="<?php echo $order['user_avatar'] ?: 'siji.png'; ?>" class="u-avatar" crossorigin="anonymous">
            <div class="u-info">
                <div class="u-left-group">
                    <div class="u-name"><?php echo htmlspecialchars($order['shop_name'] ?: '卖家'); ?></div>
                    <div class="u-tag">鱼小铺 L1</div>
                </div>
                <div class="poster-logo-right">
                    <img src="xianyu.png" class="xy-logo-img" crossorigin="anonymous">
                </div>
            </div>
        </div>
        <div class="prod-img-box">
            <img src="<?php echo $order['image']; ?>" class="prod-img" crossorigin="anonymous">
        </div>
        <div class="price-section">
            <span class="p-symbol">¥</span><span class="p-val"><?php echo $order['money']; ?></span>
        </div>
        <div class="content"><div class="c-text"><?php echo htmlspecialchars($order['product_name']); ?></div></div>
        <div style="text-align:center; padding:20px;">
            <div id="qrcode-box" style="display:inline-block;"></div>
            <div style="font-size:12px; color:#999; margin-top:10px;">长按识别二维码</div>
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
        function openMsgModal() {
            $('#msgModal').css('display', 'flex');
            $('#msgInput').val('').focus();
        }
        function closeMsgModal() {
            $('#msgModal').hide();
        }
        function sendMsg() {
            var txt = $('#msgInput').val();
            if (!txt) {
                alert('请输入留言内容');
                return;
            }
            alert('留言成功');
            closeMsgModal();
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
            setTimeout(function() {
                html2canvas(document.getElementById('poster-source'), { 
                    useCORS: true, scale: 2, logging: false, backgroundColor: '#fff' 
                }).then(canvas => {
                    $('#poster-img').attr('src', canvas.toDataURL("image/png"));
                    $('#poster-modal').css('display', 'flex').hide().fadeIn();
                });
            }, 50); 
        }

        // 微信分享配置（必须带 jsApiList 右上角「发送给朋友」才生效）
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