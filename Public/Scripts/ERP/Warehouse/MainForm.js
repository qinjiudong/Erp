// 仓库 - 主界面
Ext.define("ERP.Warehouse.MainForm", {
    extend: "Ext.panel.Panel",
    border: 0,
    layout: "border",
    initComponent: function () {
        var me = this;

        Ext.define("ERPWarehouse", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "inited"]
        });

        var grid = Ext.create("Ext.grid.Panel", {
        	border: 0,
            viewConfig: {
                enableTextSelection: true
            },
            columnLines: true,
            columns: [
                {header: "仓库编码", dataIndex: "code", menuDisabled: true, sortable: false, width: 60},
                {header: "仓库名称", dataIndex: "name", menuDisabled: true, sortable: false, width: 200},
                {header: "建账完毕", dataIndex: "inited", menuDisabled: true, sortable: false, width: 60,
                    renderer: function (value) {
                        return value == 1 ? "完毕" : "<span style='color:red'>未完</span>";
                    }}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPWarehouse",
                autoLoad: false,
                data: []
            }),
            listeners: {
                itemdblclick: {
                    fn: me.onEditWarehouse,
                    scope: me
                }
            }
        });
        me.grid = grid;

        Ext.apply(me, {
            tbar: [
                {text: "新增仓库", iconCls: "ERP-button-add", handler: this.onAddWarehouse, scope: this},
                {text: "编辑仓库", iconCls: "ERP-button-edit", handler: this.onEditWarehouse, scope: this},
                {text: "删除仓库", iconCls: "ERP-button-delete", handler: this.onDeleteWarehouse, scope: this}, "-",
                {
                    text: "帮助",
                    iconCls: "ERP-help",
                    handler: function() {
                        window.open("http://my.jyshop.net/u/134395/blog/374807");
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
                    items: [grid]
                }
            ]
        });

        me.callParent(arguments);

        me.freshGrid();
    },
    onAddWarehouse: function () {
        var form = Ext.create("ERP.Warehouse.EditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditWarehouse: function () {
        var item = this.grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的仓库");
            return;
        }

        var warehouse = item[0];

        var form = Ext.create("ERP.Warehouse.EditForm", {
            parentForm: this,
            entity: warehouse
        });

        form.show();
    },
    onDeleteWarehouse: function () {
        var item = this.grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的仓库");
            return;
        }

        var me = this;
        var warehouse = item[0];
        var info = "请确认是否删除仓库 <span style='color:red'>" + warehouse.get("name") + "</span> ?";

        var store = me.grid.getStore();
        var index = store.findExact("id", warehouse.get("id"));
        index--;
        var preIndex = null;
        var preWarehouse = store.getAt(index);
        if (preWarehouse) {
            preIndex = preWarehouse.get("id");
        }

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask(ERP.Const.LOADING);
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Warehouse/deleteWarehouse",
                params: {id: warehouse.get("id")},
                method: "POST",
                callback: function (options, success, response) {
                    el.unmask();
                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.tip("成功完成删除操作");
                            me.freshGrid(preIndex);
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                    }
                }
            });
        });
    },
    freshGrid: function (id) {
        var me = this;
        var grid = this.grid;
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Warehouse/warehouseList",
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);

                    me.gotoGridRecord(id);
                }

                el.unmask();
            }
        });
    },
    gotoGridRecord: function (id) {
        var me = this;
        var grid = me.grid;
        var store = grid.getStore();
        if (id) {
            var r = store.findExact("id", id);
            if (r != -1) {
                grid.getSelectionModel().select(r);
            } else {
                grid.getSelectionModel().select(0);
            }
        }
    }
});