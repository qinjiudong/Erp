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
        
        
<?php if($useTU == true): ?><script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/MainFormTU.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/CategoryEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/GoodsTUEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<?php else: ?>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/MainForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/CategoryEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/GoodsEditForm.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/UX/CellEditing.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Supplier/SupplierField.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script>
<script src="<?php echo ($uri); ?>Public/Scripts/ERP/Goods/CategoryField.js?dt=<?php echo ($dtFlag); ?>" type="text/javascript"></script><?php endif; ?>

<script>
    Ext.onReady(function(){
        var app = Ext.create("ERP.App", {
            userName: "<?php echo ($loginUserName); ?>"
        });

        <?php if($useTU == true): ?>app.add(Ext.create("ERP.Goods.MainFormTU"));
        <?php else: ?>
        	app.add(Ext.create("ERP.Goods.MainForm"));<?php endif; ?>
        app.setAppHeader({
            title: "<?php echo ($title); ?>",
            iconCls: "ERP-fid1001"
            });
    });
</script>
    </body>
</html>