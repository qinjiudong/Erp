Ext.define("ERP.About.MainForm", {
    extend: 'Ext.window.Window',
    header: {
        title: "<span style='font-size:120%'>关于 - 生鲜电商ERP</span>",
        iconCls: "ERP-fid-9994",
        height: 40
    },
    modal: true,
    closable: false,
    width: 400,
    layout: "fit",
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            height: 300,
            items: [{
                    border: 0,
                    xtype: "container",
                    html: "<h1>欢迎使用淘江阴生鲜电商ERP</h1><p>当前版本：" + ERP.Const.VERSION + "</p>"
                    + "<p>更多帮助请点击这里：<a href='http://my.jyshop.net/u/134395/blog/374195' target='_blank'>http://my.jyshop.net/u/134395/blog/374195</a></p>"
                    + "<p>如需技术支持，请联系：</p><p>QQ：1569352868 Email：1569352868@qq.com QQ群：414474186</p>"
                    + "<p>如需购买商业服务，请访问：<a href='http://weidian.com/?userid=315007574' target='_blank'>http://weidian.com/?userid=315007574</a></p>"
                }
            ],
            buttons: [{
                    id: "buttonOK",
                    text: "确定",
                    handler: me.onOK,
                    scope: me,
                    iconCls: "ERP-button-ok"
                }],
            listeners: {
                show: {
                    fn: me.onWndShow,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },
    onWndShow: function () {
        Ext.getCmp("buttonOK").focus();
    },
    onOK: function () {
        this.close();
    }
});