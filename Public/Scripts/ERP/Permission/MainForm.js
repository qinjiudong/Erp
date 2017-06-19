// 权限管理 - 主界面
Ext.define("ERP.Permission.MainForm", {
    extend: "Ext.panel.Panel",

    initComponent: function () {
        var me = this;

        Ext.define("ERPRole", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });

        var roleStore = Ext.create("Ext.data.Store", {
            model: "ERPRole",
            autoLoad: false,
            data: []
        });

        var roleGrid = Ext.create("Ext.grid.Panel", {
            title: "角色",
            store: roleStore,
            columns: [
                { header: "角色名称", dataIndex: "name", flex: 1, menuDisabled: true }
            ]
        });

        roleGrid.on("itemclick", me.onRoleGridItemClick, me);
        roleGrid.on("itemdblclick", me.onEditRole, me);

        Ext.define("ERPPermission", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });

        var permissionStore = Ext.create("Ext.data.Store", {
            model: "ERPPermission",
            autoLoad: false,
            data: []
        });

        var permissionGrid = Ext.create("Ext.grid.Panel", {
            store: permissionStore,
            columns: [
                { header: "权限名称", dataIndex: "name", flex: 1, menuDisabled: true }
            ]
        });

        Ext.define("ERPUser", {
            extend: "Ext.data.Model",
            fields: ["id", "loginName", "name", "orgFullName", "enabled"]
        });

        var userStore = Ext.create("Ext.data.Store", {
            model: "ERPUser",
            autoLoad: false,
            data: []
        });

        var userGrid = Ext.create("Ext.grid.Panel", {
            store: userStore,
            columns: [
                { header: "用户姓名", dataIndex: "name", flex: 1 },
                { header: "登录名", dataIndex: "loginName", flex: 1 },
                { header: "所属组织", dataIndex: "orgFullName", flex: 1 }
            ]
        });

        me.roleGrid = roleGrid;
        me.permissionGrid = permissionGrid;
        me.userGrid = userGrid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                { text: "新增角色", handler: me.onAddRole, scope: me, iconCls: "ERP-button-add" },
                { text: "编辑角色", handler: me.onEditRole, scope: me, iconCls: "ERP-button-edit" },
                { text: "删除角色", handler: me.onDeleteRole, scope: me, iconCls: "ERP-button-delete" }, "-",
                                {
                    text: "帮助",
                    iconCls: "ERP-help",
                    handler: function() {
                        window.open("http://my.jyshop.net/u/134395/blog/374337");
                    }
                },
                "-",
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [
                {
                    region: "center", xtype: "panel", layout: "fit", border: 0,
                    items: [
                        {
                            xtype: "panel",
                            layout: "border",
                            items: [
                                {
                                    xtype: "panel",
                                    region: "north",
                                    height: "50%",
                                    border: 0,
                                    split: true,
                                    layout: "fit",
                                    items: [permissionGrid]
                                },
                                {
                                    xtype: "panel",
                                    region: "center",
                                    border: 0,
                                    layout: "fit",
                                    items: [userGrid]
                                }
                            ]
                        }
                    ]
                },
                {
                    xtype: "panel",
                    region: "west",
                    layout: "fit",
                    width: 300,
                    minWidth: 200,
                    maxWidth: 350,
                    split: true,
                    border: 0,
                    items: [roleGrid]
                }
            ]
        });

        me.callParent(arguments);

        me.refreshRoleGrid();
    },

    // private
    refreshRoleGrid: function (id) {
        var grid = this.roleGrid;
        var store = grid.getStore();
        var me = this;
        Ext.getBody().mask("数据加载中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Permission/roleList",
            method: "POST",
            callback: function (options, success, response) {
                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    if (data.length > 0) {
                        if (id) {
                            var r = store.findExact("id", id);
                            if (r != -1) {
                                grid.getSelectionModel().select(r);
                            }
                        } else {
                            grid.getSelectionModel().select(0);
                        }
                        me.onRoleGridItemClick();
                    }
                }

                Ext.getBody().unmask();
            }
        });
    },

    // private
    onRoleGridItemClick: function () {
        var grid = this.permissionGrid;

        var item = this.roleGrid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }

        var role = item[0].data;
        var store = grid.getStore();
        grid.setTitle("角色 [" + role.name + "] 的权限列表");

        var el = grid.getEl() || Ext.getBody();

        el.mask("数据加载中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Permission/permissionList",
            params: { roleId: role.id },
            method: "POST",
            callback: function (options, success, response) {
                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    for (var i = 0; i < data.length; i++) {
                        var item = data[i];
                        store.add({ id: item.id, name: item.name });
                    }
                }

                el.unmask();
            }
        });

        var userGrid = this.userGrid;
        var userStore = userGrid.getStore();
        var userEl = userGrid.getEl() || Ext.getBody();
        userGrid.setTitle("属于角色 [" + role.name + "] 的人员列表");
        userEl.mask("数据加载中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Permission/userList",
            params: { roleId: role.id },
            method: "POST",
            callback: function (options, success, response) {
                userStore.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    userStore.add(data);
                }

                userEl.unmask();
            }
        });
    },

    onAddRole: function () {
        var editForm = Ext.create("ERP.Permission.EditForm", {
        	parentForm: this
        });

        editForm.show();
    },

    onEditRole: function () {
        var grid = this.roleGrid;
        var items = grid.getSelectionModel().getSelection();

        if (items == null || items.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的角色");
            return;
        }

        var role = items[0].data;

        var editForm = Ext.create("ERP.Permission.EditForm", {
            entity: role,
            parentForm: this
        });

        editForm.show();
    },

    onDeleteRole: function () {
    	var me = this;
        var grid = me.roleGrid;
        var items = grid.getSelectionModel().getSelection();

        if (items == null || items.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的角色");
            return;
        }

        var role = items[0].data;

        ERP.MsgBox.confirm("请确认是否删除角色 <span style='color:red'>" + role.name + "</span> ?",
            function () {
                Ext.getBody().mask("正在删除中...");
                Ext.Ajax.request({
                    url: ERP.Const.BASE_URL + "Home/Permission/deleteRole",
                    method: "POST",
                    params: { id: role.id },
                    callback: function (options, success, response) {
                        Ext.getBody().unmask();

                        if (success) {
                            var data = Ext.JSON.decode(response.responseText);
                            if (data.success) {
                                ERP.MsgBox.showInfo("成功完成删除操作", function () {
                                    me.refreshRoleGrid();
                                });
                            } else {
                                ERP.MsgBox.showInfo(data.msg);
                            }
                        }
                    }
                });
            });
    }
});