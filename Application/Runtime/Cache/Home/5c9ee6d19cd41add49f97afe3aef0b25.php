<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
    <head>
        <title><?php echo ($title); ?> - 生鲜电商ERP</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo ($uri); ?>Public/Images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
</head>
    <body background="<?php echo ($uri); ?>Public/Images/background.jpg" style="background-size: cover">
        

<style type="text/css">
    #loading-mask {
        background-color: white;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        z-index: 20000;
    }

    #loading {
        height: auto;
        position: absolute;
        left: 45%;
        top: 40%;
        padding: 2px;
        z-index: 20001;
    }

    #loading .loading-indicator {
        background: white;
        color: #444;
        font: bold 13px Helvetica, Arial, sans-serif;
        height: auto;
        margin: 0;
        padding: 10px;
    }

    #loading-msg {
        font-size: 10px;
        font-weight: normal;
    }
</style>
	
<div id="loading-mask" style=""></div>
<div id="loading">
    <div class="loading-indicator">
        <img src="<?php echo ($uri); ?>Public/Images/loader.gif" width="32" height="32" style="margin-right: 8px; float: left; vertical-align: top;" />
        欢迎使用生鲜电商ERP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <br />
        <span id="loading-msg">正在加载中，请稍候...</span>
    </div>
</div>
	
<script src="<?php echo ($uri); ?>Public/ExtJS/ext-all.js" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/ExtJS/ext-lang-zh_CN.js" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/MsgBox.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Const.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>

<link href="<?php echo ($uri); ?>Public/ExtJS/resources/css/ext-all.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo ($uri); ?>Public/Content/Site.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo ($uri); ?>Public/Content/icons.css" rel="stylesheet" type="text/css"/>

<script src="<?php echo ($uri); ?>Public/Scripts/ERP/User/LoginForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
	
<script type="text/javascript">
    Ext.onReady(function () {
		ERP.Const.BASE_URL = "<?php echo ($uri); ?>";
        var form = Ext.create("ERP.User.LoginForm", {
               demoInfo: "<?php echo ($demoInfo); ?>"
           });
        form.show();

        Ext.get("loading").remove();
        Ext.get("loading-mask").remove();
    });
</script>
    </body>
</html>