// 退换货入库单
Ext.define("ERP.Sale.SREditForm", {
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

        Ext.apply(me, {title: entity == null ? "新建退换货入库单" : "编辑退换货入库单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 900,
            height: 600,
            layout: "border",
            tbar:["-",{
                    text: "选择销售订单",
                    iconCls: "ERP-button-add",
                    handler: me.onSelectWSBill,
                    scope: me,
                    disabled: me.entity != null
            }, "-", {
                text: "保存",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me,
                id: "buttonSave"
            }, "-", {
                text: "取消", 
                iconCls: "ERP-button-cancel",
                handler: function () {
                	if (me.__readonly) {
                		me.close();
                		return;
                	}
                	
                	ERP.MsgBox.confirm("请确认是否取消当前操作？", function() {
                		me.close();
                	});
                }, scope: me,
                id: "buttonCancel"
            }],
            defaultFocus: "editWarehouse",
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
                    height: 130,
                    bodyPadding: 10,
                    items: [
                        {
                            xtype: "hidden",
                            id: "hiddenId",
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        },
                        {
                            id: "editCustomer",
                            xtype: "displayfield",
                            fieldLabel: "客户",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            width: 430
                        },
                        {
                            id: "editDiscount",
                            xtype: "displayfield",
                            fieldLabel: "折扣",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            width: 430
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
                            xtype: "hidden",
                            id: "editWarehouseId",
                            name: "warehouseId"
                        },
                        {
                            id: "editWarehouse",
                            fieldLabel: "入库仓库",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_warehousefield",
                            parentCmp: me,
                            fid: "2006",
                            allowBlank: false,
                            blankText: "没有输入入库仓库",
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
                        },
                        {
                            xtype : "combo",
                            id : "editRemark",  
                            //queryMode : "local",
                            editable : false,   
                            valueField : "id",
                            allowBlank: false,
                            fieldLabel:'退货原因', 
                            labelWidth : 60,
                            labelAlign : "right",
                            width:150,
                            store : Ext.create("Ext.data.ArrayStore", {
                                fields : [ "id","text" ],
                                data : [ ["",""],[ "缺货", "缺货" ], [ "质量问题", "质量问题" ],  
                                [ "测试", "测试" ], ["撤单", "撤单"]]
                            }),
                            value: ''
                        }, 
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
        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/srBillInfo",
            params: {
                id: Ext.getCmp("hiddenId").getValue()
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    if (data.ref) {
                    	// 编辑单据
                        Ext.getCmp("editRef").setValue(data.ref);
                        Ext.getCmp("editCustomer").setValue(data.customerName + " 销售单号: " + data.wsBillRef);
                        Ext.getCmp("editCustomerId").setValue(data.customerId);
                    } else {
                        // 这是：新建退货入库单
                        // 第一步就是选中销售出库单
                        me.onSelectWSBill();
                    }

                    Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    Ext.getCmp("editWarehouse").setValue(data.warehouseName);

                    Ext.getCmp("editBizUserId").setValue(data.bizUserId);
                    Ext.getCmp("editBizUser").setValue(data.bizUserName);
                    if (data.bizDT) {
                        Ext.getCmp("editBizDT").setValue(data.bizDT);
                    }
                    if (data.reason) {
                        Ext.getCmp("editRemark").setValue(data.reason);
                    }

                    var store = me.getGoodsGrid().getStore();
                    store.removeAll();
                    if (data.items) {
                        store.add(data.items);
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
        var me = this;
        //判断是否填写原因
        var reason = Ext.getCmp("editRemark").getValue();
        if(!reason){
            ERP.MsgBox.showInfo("请选择退货原因");
            return false;
        }
        Ext.getBody().mask("正在保存中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/editSRBill",
            method: "POST",
            params: {jsonStr: me.getSaveData()},
            callback: function (options, success, response) {
                Ext.getBody().unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                        ERP.MsgBox.showInfo("成功保存数据", function () {
                            me.close();
                            me.getParentForm().refreshSRBillGrid(data.id);
                        });
                    } else {
                        ERP.MsgBox.showInfo(data.msg);
                    }
                }
            }
        });
    },
    onEditBizDTSpecialKey: function (field, e) {
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
            me.getGoodsGrid().focus();
            me.__cellEditing.startEdit(0, 5);
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
        
        var modelName = "ERPSRBillDetail_EditForm";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount",
                "goodsMoney", "goodsPrice", "rejCount", "rejPrice", "rejMoney", "rejActualCount"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: modelName,
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
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 50},
                {header: "销售数量", dataIndex: "goodsCount", menuDisabled: true,
                    sortable: false, align: "right", width: 50
                },
                
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "销售金额", dataIndex: "goodsMoney", menuDisabled: true,
                    sortable: false, align: "right", xtype: "numbercolumn",
                    width: 80},

                
                {header: "销售单价", dataIndex: "goodsPrice", menuDisabled: true,
                    sortable: false, align: "right", xtype: "numbercolumn",
                    width: 80, 
                    editor: {xtype: "numberfield",
                        allowDecimals: true,
                        hideTrigger: true}
                },
                
                {header: "退货数量", dataIndex: "rejCount", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        decimalPrecision:3,
                        allowDecimals: true,
                        hideTrigger: true}
                },
                {header: "退货金额", dataIndex: "rejMoney", menuDisabled: true,
                    sortable: false, align: "right", xtype: "numbercolumn", width: 120},
                {header: "实际入库数量", dataIndex: "rejActualCount", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                    decimalPrecision:3,
                        allowDecimals: true,
                        hideTrigger: true}
                },
                {
                        header : "",
                        id: "columnActionDelete",
                        align : "center",
                        menuDisabled : true,
                        width : 50,
                        xtype : "actioncolumn",
                        items : [ {
                            icon : ERP.Const.BASE_URL
                                    + "Public/Images/icons/delete.png",
                            handler : function(grid, row) {
                                var store = grid.getStore();
                                store.remove(store.getAt(row));
                                if (store.getCount() == 0) {
                                    store.add({});
                                }
                            },
                            scope : me
                        } ]
                    } 
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
        if (e.colIdx == 8) {
            me.calcMoney();
            //e.rowIdx += 1;
            me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
            //me.__cellEditing.startEdit(e.rowIdx, 4);
        }
    },
    calcMoney: function () {
        var me = this;
        var item = me.getGoodsGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var goods = item[0];
        goods.set("rejMoney", goods.get("rejCount") * goods.get("goodsPrice"));
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
    	var me = this;
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
            customerId: Ext.getCmp("editCustomerId").getValue(),
            warehouseId: Ext.getCmp("editWarehouseId").getValue(),
            bizUserId: Ext.getCmp("editBizUserId").getValue(),
            remark: Ext.getCmp("editRemark").getValue(),
            wsBillId: me.__wsBillId,
            items: []
        };

        var store = me.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                rejCount: item.get("rejCount"),
                rejMoney: item.get("rejMoney"),
                rejActualCount: item.get("rejActualCount"),
            });
        }

        return Ext.JSON.encode(result);
    },
    onSelectWSBill: function() {
        var form = Ext.create("ERP.Sale.SRSelectWSBillForm", {
            parentForm: this
        });
        form.show();
    },
    getWSBillInfo: function(id) {
        var me = this;
        me.__wsBillId = id;
        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/getWSBillInfoForSRBill",
            params: {
                id: id
            },
            method: "POST",
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    Ext.getCmp("editCustomer").setValue(data.customerName + " 销售单号: " + data.ref);
                    Ext.getCmp("editCustomerId").setValue(data.customerId);
                    Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    Ext.getCmp("editWarehouse").setValue(data.warehouseName);
                    Ext.getCmp("editDiscount").setValue(data.discount);
                    var store = me.getGoodsGrid().getStore();
                    store.removeAll();
                    store.add(data.items);
                }

                el.unmask();
            }
        });
    },
    
    setBillReadonly: function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("查看退换货入库单");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		Ext.getCmp("editBizDT").setReadOnly(true);
		Ext.getCmp("editWarehouse").setReadOnly(true);
		Ext.getCmp("editBizUser").setReadOnly(true);
    }
});