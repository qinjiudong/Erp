Ext.define("ERP.Goods.GoodsField", {
    extend: "Ext.form.field.Trigger",
    alias: "widget.jyerp_goodsfield",

    config: {
    	parentCmp: null
    },
    
    initComponent: function () {
        this.enableKeyEvents = true;

        this.callParent(arguments);

        this.on("keydown", function (field, e) {
            if (e.getKey() == e.BACKSPACE) {
                field.setValue(null);
                e.preventDefault();
                return false;
            }

            if (e.getKey() != e.ENTER && !e.isSpecialKey(e.getKey())) {
                this.onTriggerClick(e);
            }
        });
    },

    onTriggerClick: function (e) {
        var me = this;
        var modelName = "ERPGoodsField";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "code", "barCode", "name", "spec", "unitName","bulk","bulk_str", "lastBuyPrice", "goodsCountBefore", "position","unitNamePW"]
        });

        var store = Ext.create("Ext.data.Store", {
            model: modelName,
            autoLoad: false,
            data: []
        });
        var lookupGrid = Ext.create("Ext.grid.Panel", {
            columnLines: true,
            border: 0,
            store: store,
            columns: [
                { header: "编码", dataIndex: "code", menuDisabled: true, width: 80 },
                { header: "条码", dataIndex: "barCode", menuDisabled: true, width: 80 },
                { header: "商品", dataIndex: "name", menuDisabled: true, width:200 },
                { header: "规格型号", dataIndex: "spec", menuDisabled: true, width:50 },
                { header: "库存单位", dataIndex: "unitNamePW", menuDisabled: true, width: 60 },
                { header: "单位", dataIndex: "unitName", menuDisabled: true, width: 60 },
                
                { header: "属性", dataIndex: "bulk_str", menuDisabled: true, width: 60 },
                { header: "仓位", dataIndex: "position", menuDisabled: true, width: 160 },

            ]
        });
        me.lookupGrid = lookupGrid;
        me.lookupGrid.on("itemdblclick", me.onOK, me);

        var wnd = Ext.create("Ext.window.Window", {
            title: "选择 - 商品",
            modal: true,
            width: 800,
            height: 400,
            layout: "border",
            items: [
                {
                    region: "center",
                    xtype: "panel",
                    layout: "fit",
                    border: 0,
                    items: [lookupGrid]
                },
                {
                    xtype: "panel",
                    region: "south",
                    height: 40,
                    layout: "fit",
                    border: 0,
                    items: [
                        {
                            xtype: "form",
                            layout: "form",
                            bodyPadding: 5,
                            items: [
                                {
                                    id: "__editGoods",
                                    xtype: "textfield",
                                    fieldLabel: "商品",
                                    labelWidth: 50,
                                    labelAlign: "right",
                                    labelSeparator: ""
                                }
                            ]
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: "确定", handler: me.onOK, scope: me
                },
                {
                    text: "取消", handler: function () { wnd.close(); }
                }
            ]
        });

        wnd.on("close", function () {
            me.focus();
        });
        me.wnd = wnd;

        var editName = Ext.getCmp("__editGoods");
        var timer = '0';
        editName.on("change", function () {
            var store = me.lookupGrid.getStore();
            var supplierid = Ext.getCmp("editSupplierId") && Ext.getCmp("editSupplierId").getValue();
            //var warehouseId = Ext.getCmp("editWarehouseId") && Ext.getCmp("editWarehouseId").getValue();
            var warehouseId = '';
            
            if(me.getParentCmp() && me.getParentCmp().__getWarehouseId){
                warehouseId = me.getParentCmp().__getWarehouseId();
                if(warehouseId == "undefined"){
                    warehouseId = "";
                }
            }
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Goods/queryData",
                params: {
                    queryKey: editName.getValue(),
                    supplierid:supplierid,
                    warehouseId:warehouseId
                },
                method: "POST",
                callback: function (opt, success, response) {
                    store.removeAll();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        store.add(data);
                        if (data.length > 0) {
                            //me.lookupGrid.getSelectionModel().select(0);
                            //editName.focus();
                        }
                    } else {
                        ERP.MsgBox.showInfo("网络错误");
                    }
                },
                scope: this
            });

        }, me);

        editName.on("specialkey", function (field, e) {
            if (e.getKey() == e.ENTER) {
                me.onOK();
            } else if (e.getKey() == e.UP) {
                var m = me.lookupGrid.getSelectionModel();
                var store = me.lookupGrid.getStore();
                var index = 0;
                for (var i = 0; i < store.getCount() ; i++) {
                    if (m.isSelected(i)) {
                        index = i;
                    }
                }
                index--;
                if (index < 0) {
                    index = 0;
                }
                m.select(index);
                e.preventDefault();
                editName.focus();
            } else if (e.getKey() == e.DOWN) {
                var m = me.lookupGrid.getSelectionModel();
                var store = me.lookupGrid.getStore();
                var index = 0;
                for (var i = 0; i < store.getCount() ; i++) {
                    if (m.isSelected(i)) {
                        index = i;
                    }
                }
                index++;
                if (index > store.getCount() - 1) {
                    index = store.getCount() - 1;
                }
                m.select(index);
                e.preventDefault();
                editName.focus();
            }
        }, me);

        me.wnd.on("show", function () {
            editName.focus();
            editName.fireEvent("change");
        }, me);
        wnd.show();
    },

    // private
    onOK: function () {
        var me = this;
        var grid = me.lookupGrid;
        var item = grid.getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }

        var data = item[0].getData();
        me.wnd.close();
        me.focus();
        me.setValue(data.code);
        me.focus();
        
        if (me.getParentCmp() && me.getParentCmp().__setGoodsInfo) {
        	me.getParentCmp().__setGoodsInfo(data)
        }
    }
});