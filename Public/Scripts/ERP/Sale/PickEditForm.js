// 拣货 - 新建或编辑界面
Ext.define("ERP.Sale.WSEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        me.__readonly = false;
        var entity = me.getEntity();
        this.adding = entity == null;

        Ext.apply(me, {title: entity == null ? "新建拣货单" : "编辑拣货单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 1000,
            height: 600,
            layout: "border",
            defaultFocus: "editCustomer",
            tbar: ["-", {
                text: "拣货完成",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me,
                id: "buttonSave"
            },"-",{
                text: "拣货完成",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me,
                id: "buttonSave"
            },"-",{
                text: "拣货完成",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me,
                id: "buttonSave"
            },"-",{
                text: "拣货完成",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me,
                id: "buttonSave"
            },"-", {
                text: "取消", 
                iconCls: "ERP-button-cancel",
                handler: function () {
                	if (me.__readonly) {
                		me.close();
                		return;
                	}
                	
                	ERP.MsgBox.confirm("请确认是否取消当前操作？", function(){
                		me.close();
                	});
                }, scope: me,
                id: "buttonCancel"
            }],
            items: [{
                    region: "center",
                    border: 0,
                    bodyPadding: 10,
                    layout: "fit",
                    items: [me.getGoodsGrid()]
                },
                {
                    region: "north",
                    border: 0,
                    layout: {
                        type: "table",
                        columns: 2
                    },
                    height: 100,
                    bodyPadding: 10,
                    items: [
                        {
                            xtype: "hidden",
                            id: "hiddenId",
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        },
                        {
                            id: "editRef",
                            fieldLabel: "单号",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "displayfield",
                            value: "<span style='color:red'>保存后自动生成</span>"
                        },
                        {
                            id: "editBizDT",
                            fieldLabel: "业务日期",
                            allowBlank: false,
                            blankText: "没有输入业务日期",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            xtype: "datefield",
                            format: "Y-m-d",
                            value: new Date(),
                            name: "bizDT",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditBizDTSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: "hidden",
                            id: "editCustomerId",
                            name: "customerId"
                        },
                        {
                            id: "editCustomer",
                            xtype: "jyerp_customerfield",
                            parentCmp: me,
                            fieldLabel: "客户",
                            allowBlank: false,
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            colspan: 2,
                            width: 430,
                            blankText: "没有输入客户",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCustomerSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: "hidden",
                            id: "editWarehouseId",
                            name: "warehouseId"
                        },
                        {
                            id: "editWarehouse",
                            fieldLabel: "出库仓库",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_warehousefield",
                            parentCmp: me,
                            fid: "2002",
                            allowBlank: false,
                            blankText: "没有输入出库仓库",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditWarehouseSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: "hidden",
                            id: "editBizUserId",
                            name: "bizUserId"
                        },
                        {
                            id: "editBizUser",
                            fieldLabel: "业务员",
                            xtype: "jyerp_userfield",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            parentCmp: me,
                            allowBlank: false,
                            blankText: "没有输入业务员",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditBizUserSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ]
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
        var me = this;
        me.__canEditGoodsPrice = false;
        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/wsBillInfo",
            params: {
                id: Ext.getCmp("hiddenId").getValue()
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    if (data.canEditGoodsPrice == 'xx') {
                        me.__canEditGoodsPrice = true;
                        Ext.getCmp("columnGoodsPrice").setEditor({xtype: "numberfield",
                            allowDecimals: true,
                            hideTrigger: true});
                    }
                    
                    if (data.ref) {
                        Ext.getCmp("editRef").setValue(data.ref);
                    }

                    Ext.getCmp("editCustomerId").setValue(data.customerId);
                    Ext.getCmp("editCustomer").setValue(data.customerName);

                    Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    Ext.getCmp("editWarehouse").setValue(data.warehouseName);

                    Ext.getCmp("editBizUserId").setValue(data.bizUserId);
                    Ext.getCmp("editBizUser").setValue(data.bizUserName);
                    if (data.bizDT) {
                        Ext.getCmp("editBizDT").setValue(data.bizDT);
                    }

                    var store = me.getGoodsGrid().getStore();
                    store.removeAll();
                    if (data.items) {
                        store.add(data.items);
                    }
                    if (store.getCount() == 0) {
                        store.add({});
                    }

                    if (data.billStatus && data.billStatus != 0) {
                    	me.setBillReadonly();
                    }
                } else {
                    ERP.MsgBox.showInfo("网络错误")
                }
            }
        });
    },
    // private
    onOK: function () {
        var info = "确认要将次订单置为拣货完成吗?";
        var me = this;
        var entity = me.getEntity();
        ERP.MsgBox.confirm(info, function () {
            Ext.getBody().mask("正在保存中...");
            Ext.Ajax.request({
                url: ERP.Const.BASE_URL + "Home/sale/doPick",
                method: "POST",
                params: {items: me.getItems(),id:entity.get("id"),pick_type:1,action:"pick_return"},
                callback: function (options, success, response) {
                    Ext.getBody().unmask();

                    if (success) {
                        var data = Ext.JSON.decode(response.responseText);
                        if (data.success) {
                            ERP.MsgBox.showInfo("成功出库", function () {
                                me.close();
                                me.getParentForm().refreshPickBillGrid();
                            });
                        } else {
                            ERP.MsgBox.showInfo(data.msg);
                        }
                    }
                }
            });
        });
        
        
        
    },
    onEditBizDTSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editCustomer").focus();
        }
    },
    onEditCustomerSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editWarehouse").focus();
        }
    },
    onEditWarehouseSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editBizUser").focus();
        }
    },
    onEditBizUserSpecialKey: function (field, e) {
    	if (this.__readonly) {
    		return;
    	}
    	
        if (e.getKey() == e.ENTER) {
            var me = this;
            var store = me.getGoodsGrid().getStore();
            if (store.getCount() == 0) {
                store.add({});
            }
            me.getGoodsGrid().focus();
            me.__cellEditing.startEdit(0, 1);
        }
    },
    // CustomerField回调此方法
    __setCustomerInfo: function (data) {
        Ext.getCmp("editCustomerId").setValue(data.id);
    },
    // WarehouseField回调此方法
    __setWarehouseInfo: function (data) {
        Ext.getCmp("editWarehouseId").setValue(data.id);
    },
    // UserField回调此方法
    __setUserInfo: function (data) {
        Ext.getCmp("editBizUserId").setValue(data.id);
    },
    getGoodsGrid: function () {
        var me = this;
        if (me.__goodsGrid) {
            return me.__goodsGrid;
        }
        Ext.define("ERPWSBillDetail_EditForm", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount",
                "goodsMoney", "goodsPrice", "bulk", "bulkStr", "goods_attr", "apply_price", "apply_count", "apply_num","delivery_date","delivery_time"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPWSBillDetail_EditForm",
            data: []
        });

        me.__cellEditing = Ext.create("ERP.UX.CellEditing", {
        	clicksToEdit: 1,
            listeners: {
                edit: {
                    fn: me.cellEditingAfterEdit,
                    scope: me
                }
            }
        });

        me.__goodsGrid = Ext.create("Ext.grid.Panel", {
        	viewConfig: {
                enableTextSelection: true
            },
            plugins: [me.__cellEditing],
            columnLines: true,
            columns: [
                Ext.create("Ext.grid.RowNumberer", {text: "序号", width: 30}),
                {header: "商品编码", dataIndex: "goodsCode", menuDisabled: true,
                    sortable: false},
                {header: "送货日期", dataIndex: "delivery_date", menuDisabled: true, sortable: false, width: 80},
                {header: "送货时间", dataIndex: "delivery_time", menuDisabled: true, sortable: false, width: 60},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 80},
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 40},
                {header: "销售总额", dataIndex: "goodsMoney", menuDisabled: true, sortable: false, width: 70},
                {header: "计数单位", dataIndex: "bulkStr", menuDisabled: true, sortable: false, width: 60},
                {header: "销售数量", dataIndex: "goodsCount", menuDisabled: true, sortable: false, width: 60},
                {header: "销售单价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, width: 70, id:"columnGoodsPrice"},
                
                {header: "执行数量", dataIndex: "apply_count", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: false,
                        hideTrigger: true}
                },
                {header: "执行重量", dataIndex: "apply_num", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: true,
                        hideTrigger: true}
                },
                {header: "执行价格", dataIndex: "apply_price", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: true,
                        hideTrigger: true}
                },
                {
                    header: "状态", 
                    dataIndex: "oos", 
                    menuDisabled: true,
                    sortable: false, 
                    align: "right", 
                    width: 100,
                    editor: {
                        xtype : "combo", 
                        queryMode : "local",
                        editable : false, 
                        valueField : "text",
                        displayField : "text",
                        store : Ext.create("Ext.data.ArrayStore", {
                            fields : [ "id", "text" ],
                            data : [  [ "0", "正常" ], [ "1", "缺货" ] ]
                        }),
                        value: "0"
                    },
                    value: "0"
                },
                {header: "备注", dataIndex: "remark", menuDisabled: true,
                    sortable: false},
                
            ],
            store: store,
            listeners: {
            	cellclick: function() {
					return !me.__readonly;
				}
            }
        });

        return me.__goodsGrid;
    },
    cellEditingAfterEdit: function (editor, e) {
        var me = this;
        if (e.colIdx == 4) {
            me.calcMoney();
            if (!me.__canEditGoodsPrice) {
                var store = me.getGoodsGrid().getStore();
                if (e.rowIdx == store.getCount() - 1) {
                    store.add({});
                }
                e.rowIdx += 1;
                me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
                me.__cellEditing.startEdit(e.rowIdx, 1);
            }
        } else if (e.colIdx == 6) {
            me.calcMoney();
            var store = me.getGoodsGrid().getStore();
            if (e.rowIdx == store.getCount() - 1) {
                store.add({});
            }
            e.rowIdx += 1;
            me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
            me.__cellEditing.startEdit(e.rowIdx, 1);
        } else if (e.colIdx == 11){
            me.calcMoney();
        } else if (e.cloIdx == 12){
            me.calcMoney();
        }
    },
    calcMoney: function () {
        var me = this;
        var item = me.getGoodsGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var goods = item[0];
        if(goods.get("bulk") == 0){
            goods.set("apply_price", goods.get("apply_num") * goods.get("goodsPrice"));
        } else {
            goods.set("apply_price", goods.get("apply_count") * goods.get("goodsPrice"));
        }
        
    },
    __setGoodsInfo: function (data) {
        var me = this;
        var item = me.getGoodsGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var goods = item[0];

        goods.set("goodsId", data.id);
        goods.set("goodsCode", data.code);
        goods.set("goodsName", data.name);
        goods.set("unitName", data.unitName);
        goods.set("goodsSpec", data.spec);
        goods.set("goodsPrice", data.salePrice);
    },
    getSaveData: function () {
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
            customerId: Ext.getCmp("editCustomerId").getValue(),
            warehouseId: Ext.getCmp("editWarehouseId").getValue(),
            bizUserId: Ext.getCmp("editBizUserId").getValue(),
            items: []
        };

        var store = this.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                goodsCode: item.get("goodsCode"),
                apply_price:item.get("apply_price"),
                apply_count:item.get("apply_count"),
                apply_num:item.get("apply_num")
            });
        }

        return Ext.JSON.encode(result);
    },
    getItems: function () {
        var result = {
            items: []
        };

        var store = this.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                goodsCode: item.get("goodsCode"),
                goodsCount: item.get("goodsCount"),
                apply_price:item.get("apply_price"),
                apply_count:item.get("apply_count"),
                apply_num:item.get("apply_num"),
                oos : item.get("oos")
            });
        }

        return Ext.JSON.encode(result.items);
    },

    setBillReadonly: function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("查看拣货单");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		Ext.getCmp("editBizDT").setReadOnly(true);
		Ext.getCmp("editCustomer").setReadOnly(true);
		Ext.getCmp("editWarehouse").setReadOnly(true);
		Ext.getCmp("editBizUser").setReadOnly(true);
		Ext.getCmp("columnActionDelete").hide();
    }
});