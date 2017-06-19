// 补货单 - 新建或编辑界面
Ext.define("ERP.Sale.RSEditForm", {
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

        Ext.apply(me, {title: entity == null ? "新建补货单" : "编辑补货单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 1000,
            height: 600,
            layout: "border",
            defaultFocus: "editCustomer",
            tbar: ["-",{
                    text: "选择需要补货的订单",
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
                    height: 150,
                    bodyPadding: 10,
                    items: [
                        {
                            xtype: "hidden",
                            id: "hiddenId",
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        },
                        {
                            xtype: "hidden",
                            id: "editWSBillId",
                            name: "WSBillId"
                        },
                        {
                            xtype: "hidden",
                            id: "editBillType",
                            name: "BillType",
                            value: 1
                        },
                        {
                            xtype:"hidden",
                            id:"editConsignee",
                            name : "editConsignee",
                            value :""
                        },
                        {
                            xtype:"hidden",
                            id:"editMobile",
                            name : "editMobile",
                            value :""
                        },
                        {
                            id: "editCustomer",
                            xtype: "displayfield",
                            fieldLabel: "客户",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            colspan: 2,
                            width: 430
                        },
                        {
                            id: "editDeliveryDate",
                            fieldLabel: "送货日期",
                            allowBlank: false,
                            blankText: "送货日期",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            xtype: "datefield",
                            format: "Y-m-d",
                            value: new Date(),
                            name: "deliveryDate",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditBizDTSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype : "combo",
                            id : "editDeliveryTime",
                            name: "deliveryTime",
                            width:60, 
                            queryMode : "local",
                            editable : false,   
                            valueField : "id",
                            displayField: "name",
                            store : Ext.create("Ext.data.ArrayStore", {
                                fields : [ "id", "name" ],
                                data : [[ "1", "上午" ], [ "2", "下午" ] ]
                            }),
                            value: '1'
                        },
                        {
                            id: "editSitename",
                            name:"siteid",
                            fieldLabel: "站点",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_sitefield",
                            parentCmp: me,
                            allowBlank: false,
                            blankText: "没有输入站点",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditWarehouseSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editAddress",
                            fieldLabel: "地址",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "textfield",
                            name : "address",
                            value: ""
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
                            xtype: "hidden",
                            id: "editSiteId",
                            name: "siteId"
                        },
                        {
                            id: "editWarehouse",
                            fieldLabel: "仓库",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_warehousefield",
                            parentCmp: me,
                            fid: "2006",
                            allowBlank: false,
                            blankText: "没有输入仓库",
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

                    if (data.canEditGoodsPrice) {
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
        var me = this;
        Ext.getBody().mask("正在保存中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/editWSBill",
            method: "POST",
            params: {jsonStr: me.getSaveData()},
            callback: function (options, success, response) {
                Ext.getBody().unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                        ERP.MsgBox.showInfo("成功保存数据", function () {
                            me.close();
                            me.getParentForm().refreshWSBillGrid(data.id);
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
                "goodsMoney", "goodsPrice"]
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
                    sortable: false, editor: {xtype: "jyerp_goods_with_saleprice_field", parentCmp: me}},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                {header: "补货数量", dataIndex: "goodsCount", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: false,
                        hideTrigger: true}
                },
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "补货单价", dataIndex: "goodsPrice", menuDisabled: true,
                    sortable: false, align: "right", xtype: "numbercolumn",
                    width: 100, id: "columnGoodsPrice"},
                {header: "补货金额", dataIndex: "goodsMoney", menuDisabled: true,
                    sortable: false, align: "right", xtype: "numbercolumn", width: 120},
                {
                    header: "",
                    align: "center",
                    menuDisabled: true,
                    width: 50,
                    xtype: "actioncolumn",
                    id: "columnActionDelete",
                    items: [
                        {
                            icon: ERP.Const.BASE_URL + "Public/Images/icons/delete.png",
                            handler: function (grid, row) {
                                var store = grid.getStore();
                                store.remove(store.getAt(row));
                                if (store.getCount() == 0) {
									store.add({});
								}
                            }, scope: me
                        }
                    ]
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
        }
    },
    calcMoney: function () {
        var me = this;
        var item = me.getGoodsGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var goods = item[0];
        goods.set("goodsMoney", goods.get("goodsCount") * goods.get("goodsPrice"));
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
    __setSiteInfo: function(data){
        Ext.getCmp("editSiteId").setValue(data.id);
    },
    getSaveData: function () {
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
            customerId: Ext.getCmp("editCustomerId").getValue(),
            warehouseId: Ext.getCmp("editWarehouseId").getValue(),
            bizUserId: Ext.getCmp("editBizUserId").getValue(),
            WSBillId: Ext.getCmp("editWSBillId").getValue(),//关联的订单号，给这个订单号补货
            BillType: Ext.getCmp("editBillType").getValue(),//类别是补货单
            deliveryDate : Ext.getCmp("editDeliveryDate").getValue(),
            deliveryTime : Ext.getCmp("editDeliveryTime").getValue(),
            address : Ext.getCmp("editAddress").getValue(),//送货地址
            siteId : Ext.getCmp("editSiteId").getValue(),//送货站点id
            mobile: Ext.getCmp("editMobile").getValue(),
            consignee:Ext.getCmp("editConsignee").getValue(),
            items: []
        };

        var store = this.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                goodsCount: item.get("goodsCount"),
                goodsPrice: item.get("goodsPrice")
            });
        }

        return Ext.JSON.encode(result);
    },
    getWSBillInfo: function(id) {
        var me = this;
        me.__wsBillId = id;
        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Sale/getWSBillInfoForRSBill",
            params: {
                id: id
            },
            method: "POST",
            callback: function (options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    Ext.getCmp("editCustomer").setValue(data.customerName + " 销售单号: " + data.ref + "手机:" + data.mobile);
                    Ext.getCmp("editConsignee").setValue(data.customerName);

                    Ext.getCmp("editMobile").setValue(data.mobile);
                    Ext.getCmp("editCustomerId").setValue(data.customerId);
                    Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    Ext.getCmp("editWarehouse").setValue(data.warehouseName);
                    Ext.getCmp("editSitename").setValue(data.sitename);
                    Ext.getCmp("editAddress").setValue(data.address);
                    Ext.getCmp("editSiteId").setValue(data.siteid);
                    Ext.getCmp("editWSBillId").setValue(data.id);
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
		me.setTitle("查看补货单");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		Ext.getCmp("editBizDT").setReadOnly(true);
		Ext.getCmp("editCustomer").setReadOnly(true);
		Ext.getCmp("editWarehouse").setReadOnly(true);
		Ext.getCmp("editBizUser").setReadOnly(true);
		Ext.getCmp("columnActionDelete").hide();
    },
    onSelectWSBill: function() {
        var form = Ext.create("ERP.Sale.RSSelectWSBillForm", {
            parentForm: this
        });
        form.show();
    },
});