Ext.define("ERP.Purchase.PurchaseField", {
    extend: "Ext.form.field.Trigger",
    alias: "widget.jyerp_purchasefield",

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
        var modelName = "ERPPurchaseField";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["ref", "biz_dt", "supplier", 'supplier_code', "goods_money", "warehouse","supplier_id", "warehouse_id"]
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
                { header: "采购单编号", dataIndex: "ref", menuDisabled: true, width: 100 },
                { header: "日期", dataIndex: "biz_dt", menuDisabled: true, flex: 1,width:100 },
                { header: "供应商", dataIndex: "supplier", menuDisabled: true, flex: 1, width:100 },
                { header: "供应商编码", dataIndex: "supplier_code", menuDisabled: true, flex: 1, width:100 },
                { header: "金额", dataIndex: "goods_money", menuDisabled: true, width: 60 },
                { header: "仓库", dataIndex: "warehouse", menuDisabled: true, width: 80 }
            ]
        });
        me.lookupGrid = lookupGrid;
        me.lookupGrid.on("itemdblclick", me.onOK, me);

        var wnd = Ext.create("Ext.window.Window", {
            title: "选择 - 采购单",
            modal: true,
            width: 700,
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
                                    fieldLabel: "采购单号或日期",
                                    labelWidth: 100,
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
        editName.on("change", function () {
            var store = me.lookupGrid.getStore();
            var supplierid = Ext.getCmp("editSupplierId").getValue();
            var ia = 0;
            if(me.getParentCmp() && me.getParentCmp().__ia){
                ia = 1;
            } else {
                ia = 0;
            }
            
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/Purchase/queryData",
                params: {
                    queryKey: editName.getValue(),
                    supplierid:supplierid,
                    bill_status:"1",
                    ia:ia
                },
                method: "POST",
                callback: function (opt, success, response) {
                    store.removeAll();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        store.add(data);
                        if (data.length > 0) {
                            me.lookupGrid.getSelectionModel().select(0);
                            editName.focus();
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
        
        if (me.getParentCmp() && me.getParentCmp().__setPurchaseInfo) {
        	me.getParentCmp().__setPurchaseInfo(data)
        }
    }
});