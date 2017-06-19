// 应用容器：承载主菜单、其他模块的UI
Ext.define("ERP.App", {
    config: {userName: ""},

    constructor: function (config) {
        this.initConfig(config);
        this.createMainUI();
        var me = this;
    },

    createMainUI: function () {
        var me = this;

        me.mainPanel = Ext.create("Ext.panel.Panel", {
            border: 0,
            layout: "fit"
        });

        Ext.define("ERPFId", {
            extend: "Ext.data.Model",
            fields: ["fid", "name"]
        });


        me.vp = Ext.create("Ext.container.Viewport", {
            layout: "fit",
            items: [{
                id: "__ERPTopPanel",
                xtype: "panel",
                border: 0,
                layout: "border",
                bbar: ["当前用户：<span style='color:red'>" + me.getUserName() + "</span>"],
                items: [{
                    region: "center", layout: "fit", xtype: "panel",
                    items: [me.mainPanel]
                }]

            }]
        });

        var el = Ext.getBody();

        el.mask("系统正在加载中...");

        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/MainMenu/mainMenuItems",
            method: "POST",
            callback: function (opt, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    me.createMainMenu(data);

                }

                el.unmask();
            },
            scope: me
        });
    },

    createMainMenu: function (root) {
        var me = this;

        var menuItemClick = function () {
            var fid = this.fid;

            if (fid == "-9995") {
                window.open("http://my.jyshop.net/u/134395/blog/374195");
            } else if (fid == "-9994") {
                Ext.create("ERP.About.MainForm").show();
            } else if (fid == "-9993") {
                window.open("http://weidian.com/?userid=315007574");
            } else if (fid === "-9999") {
                ERP.MsgBox.confirm("请确认是否重新登录", function () {
                    location.replace(ERP.Const.BASE_URL + "Home/MainMenu/navigateTo/fid/-9999");
                });
            } else {
                location.replace(ERP.Const.BASE_URL + "Home/MainMenu/navigateTo/fid/" + fid);
            }
        };

        var mainMenu = [];
        for (var i = 0; i < root.length; i++) {
            var m1 = root[i];

            var menuItem = Ext.create("Ext.menu.Menu");
            for (var j = 0; j < m1.children.length; j++) {
                var m2 = m1.children[j];

                if (m2.children.length === 0) {
                    // 只有二级菜单
                    menuItem.add({
                        text: m2.caption, fid: m2.fid, handler: menuItemClick,
                        iconCls: "ERP-fid" + m2.fid
                    });
                } else {
                    var menuItem2 = Ext.create("Ext.menu.Menu");

                    menuItem.add({text: m2.caption, menu: menuItem2});

                    // 三级菜单
                    for (var k = 0; k < m2.children.length; k++) {
                        var m3 = m2.children[k];
                        menuItem2.add({
                            text: m3.caption, fid: m3.fid, handler: menuItemClick,
                            iconCls: "ERP-fid" + m3.fid
                        });
                    }
                }
            }

            mainMenu.push({text: m1.caption, menu: menuItem});
        }

        var mainToolbar = Ext.create("Ext.toolbar.Toolbar", {
            dock: "top"
        });
        mainToolbar.add(mainMenu);

        this.vp.getComponent(0).addDocked(mainToolbar);
    },

    setAppHeader: function (header) {
        if (!header) {
            return;
        }
        var panel = Ext.getCmp("__ERPTopPanel");
        panel.setTitle(header.title + " - 电商ERP");
        panel.setIconCls(header.iconCls);
    },

    add: function (comp) {
        this.mainPanel.add(comp);
    }

});