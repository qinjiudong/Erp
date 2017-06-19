Ext.define("ERP.Goods.UnitMainForm", {
    extend: "Ext.panel.Panel",
    initComponent: function () {
        var me = this;

        Ext.define("ERPGoodsUnit", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });

        var grid = Ext.create("Ext.grid.Panel", {
            columnLines: true,
            columns: [
                {header: "商品计量单位", dataIndex: "name", menuDisabled: true, sortable: false, width: 200}
            ],
            store: Ext.create("Ext.data.Store", {
                model: "ERPGoodsUnit",
                autoLoad: false,
                data: []
            }),
            listeners: {
                itemdblclick: {
                    fn: me.onEditUnit,
                    scope: me
                }
            }
        });
        this.grid = grid;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [
                {text: "新增计量单位", iconCls: "ERP-button-add", handler: me.onAddUnit, scope: me},
                {text: "编辑计量单位", iconCls: "ERP-button-edit", handler: me.onEditUnit, scope: me},
                {text: "删除计量单位", iconCls: "ERP-button-delete", handler: me.onDeleteUnit, scope: me}, "-",
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
    onAddUnit: function () {
        var form = Ext.create("ERP.Goods.UnitEditForm", {
            parentForm: this
        });

        form.show();
    },
    onEditUnit: function () {
        var item = this.grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要编辑的商品计量单位");
            return;
        }

        var unit = item[0];

        var form = Ext.create("ERP.Goods.UnitEditForm", {
            parentForm: this,
            entity: unit
        });

        form.show();
    },
    onDeleteUnit: function () {
        var item = this.grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            ERP.MsgBox.showInfo("请选择要删除的商品计量单位");
            return;
        }

        var me = this;
        var unit = item[0];
        var info = "请确认是否删除商品计量单位 <span style='color:red'>" + unit.get("name") + "</span> ?";

        var store = me.grid.getStore();
        var index = store.findExact("id", unit.get("id"));
        index--;
        var preIndex = null;
        var preItem = store.getAt(index);
        if (preItem) {
            preIndex = preItem.get("id");
        }

        ERP.MsgBox.confirm(info, function () {
            var el = Ext.getBody();
            el.mask(ERP.Const.LOADING);
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/deleteUnit",
                params: {id: unit.get("id")},
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
        var grid = me.grid;

        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Goods/allUnits",
            method: "POST",
            callback: function (options, success, response) {
                var store = grid.getStore();

                store.removeAll();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    store.add(data);
                    if (id) {
                        var r = store.findExact("id", id);
                        if (r != -1) {
                            grid.getSelectionModel().select(r);
                        } else {
                            grid.getSelectionModel().select(0);
                        }

                    }
                }

                el.unmask();
            }
        });
    }
});