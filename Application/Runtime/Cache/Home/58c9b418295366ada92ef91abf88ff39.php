<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
    <head>
        <title><?php echo ($title); ?> - 生鲜电商ERP</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo ($uri); ?>Public/Images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <script src="<?php echo ($uri); ?>Public/ExtJS/ext-all.js" type="text/javascript"></script>
        <script src="<?php echo ($uri); ?>Public/ExtJS/ext-lang-zh_CN.js" type="text/javascript"></script>
        <script src="<?php echo ($uri); ?>Public/Scripts/ERP/MsgBox.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
        <script src="<?php echo ($uri); ?>Public/Scripts/ERP/Const.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
        <script src="<?php echo ($uri); ?>Public/Scripts/ERP/About/MainForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>

        <link href="<?php echo ($uri); ?>Public/ExtJS/resources/css/ext-all.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo ($uri); ?>Public/Content/Site.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo ($uri); ?>Public/Content/icons.css" rel="stylesheet" type="text/css"/>

        <script src="<?php echo ($uri); ?>Public/Scripts/ERP/App.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>

        <!-- 引入打印插件 -->
        <script language="javascript" src="<?php echo ($uri); ?>Public/Scripts/ERP/LodopFuncs.js"></script>

</head>
    <body>
    <script>
        ERP.Const.BASE_URL = "<?php echo ($uri); ?>";    
    </script>
        
        

<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/MainForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/GoodsEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/ShopEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/ShopEditor.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/WarehouseEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/CategoryEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/Categoryfield.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/Categoryfield_1.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Shop/UserField.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Warehouse/WarehouseField.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>

<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/GoodsField.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script>
    Ext.onReady(function(){
        var app = Ext.create("ERP.App", {
            userName: "<?php echo ($loginUserName); ?>"
        });

        app.add(Ext.create("ERP.Shop.MainForm"));
        app.setAppHeader({
            title: "<?php echo ($title); ?>",
            iconCls: "ERP-fid1004"
            });
    });
</script>
    </body>
</html>