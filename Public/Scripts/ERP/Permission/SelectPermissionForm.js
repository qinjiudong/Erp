// 选择权限
Ext.define("ERP.Permission.SelectPermissionForm", {
    extend: "Ext.window.Window",

    config: {
        idList: null, // idList是数组
        parentForm: null
    },

    title: "选择权限",
    width: 400,
    height: 300,
    modal: true,
    layout: "fit",

    initComponent: function () {
        var me = this;
        Ext.define("ERPPermission_SelectPermissionForm", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });

        var permissionStore = Ext.create("Ext.data.Store", {
            model: "ERPPermission_SelectPermissionForm",
            autoLoad: false,
            data: []
        });

        var permissionGrid = Ext.create("Ext.grid.Panel", {
            title: "角色的权限",
            padding: 5,
            selModel: {
                mode: "MULTI"
            },
            selType: "checkboxmodel",
            viewConfig: {
                deferEmptyText: false,
                emptyText: "所有权限都已经加入到当前角色中了"
            },
            store: permissionStore,
            columns: [
                { header: "权限名称", dataIndex: "name", flex: 1, menuDisabled: true }
            ]
        });

        this.permissionGrid = permissionGrid;

        Ext.apply(me, {
            items: [permissionGrid],
            buttons: [{
                text: "确定",
                formBind: true,
                iconCls: "ERP-button-ok",
                handler: this.onOK,
                scope: this
            }, { text: "取消", handler: function () { me.close(); }, scope: me }
            ],
            listeners: {
                show: me.onWndShow
            }
        });

        me.callParent(arguments);
    },

    onWndShow: function () {
        var me = this;
        var idList = me.getIdList();
        var permissionStore = me.permissionGrid.getStore();

        var el = me.getEl() || Ext.getBody();
        el.mask("数据加载中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Permission/selectPermission",
            params: { idList: idList.join() },
            method: "POST",
            callback: function (options, success, response) {
                permissionStore.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    for (var i = 0; i < data.length; i++) {
                        var item = data[i];
                        permissionStore.add({ id: item.id, name: item.name });
                    }
                }

                el.unmask();
            }
        });
    },

    onOK: function () {
        var grid = this.permissionGrid;

        var items = grid.getSelectionModel().getSelection();
        if (items == null || items.length == 0) {
            ERP.MsgBox.showInfo("没有选择权限");

            return;
        }

        if (this.getParentForm()) {
            this.getParentForm().setSelectedPermission(items);
        }

        this.close();
    }
});