// 信息提示框
Ext.define("ERP.MsgBox", {
    statics: {
        // 显示提示信息
        showInfo: function (info, func) {
            Ext.Msg.show({
                title: "提示",
                msg: info,
                icon: Ext.Msg.INFO,
                buttons: Ext.Msg.OK,
                modal: true,
                fn: function () {
                    if (func) {
                        func();
                    }
                }
            });
        },
        // 显示确认信息
        confirm: function (confirmInfo, funcOnYes) {
            Ext.Msg.show({
                title: "提示",
                msg: confirmInfo,
                icon: Ext.Msg.QUESTION,
                buttons: Ext.Msg.YESNO,
                modal: true,
                defaultFocus: "no",
                fn: function (id) {
                    if (id === "yes" && funcOnYes) {
                        funcOnYes();
                    }
                }
            });
        },
        // 显示提示信息，提示信息会自动关闭
        tip: function (info) {
            var wnd = Ext.create("Ext.window.Window", {
                modal: false,
                onEsc: Ext.emptyFn,
                width: 300,
                height: 100,
                header: false,
                laytout: "fit",
                border: 0,
                items: [
                    {
                        xtype: "container",
                        html: "<h3>提示</h3><p>" + info + "</p>"
                    }
                ]
            });

            wnd.showAt(document.body.clientWidth / 2 - 150, 0);

            Ext.Function.defer(function () {
                wnd.hide();
                wnd.close();
            }, 2000);
        },

        tip_bottom: function (info, autoHide) {
            var wnd = Ext.create("Ext.window.Window", {
                modal: false,
                onEsc: Ext.emptyFn,
                width: 300,
                height: 200,
                header: {
                    title: "<span>待处理小提示</span>",
                    iconCls: "ERP-portal-purchase",
                    height: 20
                },
                laytout: "fit",
                border: 0,
                items: [
                    {
                        xtype: "container",
                        html: "<p>" + info + "</p>"
                    }
                ]
            });

            wnd.showAt(document.body.clientWidth - 300, document.body.clientHeight - 200);
            if (autoHide != "undefiend" && autoHide == true) {
                Ext.Function.defer(function () {
                    wnd.hide();
                    wnd.close();
                }, 2000);
            }

        }
    }
});