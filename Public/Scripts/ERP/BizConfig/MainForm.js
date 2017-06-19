// 业务设置 - 主窗体
Ext.define("ERP.BizConfig.MainForm", {
    extend: "Ext.panel.Panel",
    
    initComponent: function () {
        var me = this;

        Ext.apply(me, {
            border: 0,
            layout: "border",
            tbar: [{
                    text: "设置", iconCls: "ERP-button-edit", handler: me.onEdit, scope: me
                }, "-",
                {
                    text: "关闭", iconCls: "ERP-button-exit", handler: function () {
                        location.replace(ERP.Const.BASE_URL);
                    }
                }
            ],
            items: [
                {
                    region: "center", layout: "fit", xtype: "panel", border: 0,
                    items: [me.getGrid()]
                }
            ]
        });

        me.callParent(arguments);

        me.refreshGrid();
    },
    
    getGrid: function () {
        var me = this;
        if (me.__grid) {
            return me.__grid;
        }

        var modelName = "PSIBizConfig";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "name", "value", "displayValue", "note"],
            idProperty: "id"
        });
        var store = Ext.create("Ext.data.Store", {
            model: modelName,
            data: [],
            autoLoad: false
        });

        me.__grid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            loadMask: true,
            border: 0,
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 40}),
                {text: "设置项", dataIndex: "name", width: 250, menuDisabled: true},
                {text: "值", dataIndex: "displayValue", width: 500, menuDisabled: true},
                {text: "备注", dataIndex: "note", width: 500, menuDisabled: true}
            ],
            store: store,
            listeners: {
                itemdblclick: {
                    fn: me.onEdit,
                    scope: me
                }
            }
        });

        return me.__grid;
    },
    
    refreshGrid: function (id) {
        var me = this;
        var grid = me.getGrid();
        var el = grid.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/BizConfig/allConfigs",
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
                        }
                    } else {
                        grid.getSelectionModel().select(0);
                    }
                }

                el.unmask();
            }
        });
    },
    
    // 设置按钮被单击
    onEdit: function () {
        var form = Ext.create("ERP.BizConfig.EditForm", {
            parentForm: this
        });
        form.show();
    }
});