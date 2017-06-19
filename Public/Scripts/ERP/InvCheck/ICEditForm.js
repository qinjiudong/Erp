// 盘点单
Ext.define("ERP.InvCheck.ICEditForm", {
    extend: "Ext.window.Window",
    
    config: {
        parentForm: null,
        entity: null
    },
    
    initComponent: function () {
        var me = this;
        me.__readonly = false;
        var entity = me.getEntity();
        me.adding = entity == null;

        Ext.apply(me, {title: entity == null ? "新建盘点单" : "编辑盘点单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 1000,
            height: 600,
            layout: "border",
            defaultFocus: "editWarehouse",
            tbar: ["-", {
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
                        columns: 5
                    },
                    height: 35,
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
                            id: "editWarehouseId",
                            name: "warehouseId"
                        },
                        {
                            xtype: "hidden",
                            id: "editSupplierId",
                            name: "editSupplierId"
                        },
                        {
                            id: "editWarehouse",
                            fieldLabel: "盘点仓库",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_warehousefield",
                            callbackFunc: me.__setWarehouseId,
                            fid: "2010",
                            allowBlank: false,
                            blankText: "没有输入盘点仓库",
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
            url: ERP.Const.BASE_URL + "Home/InvCheck/icBillInfo",
            params: {
                id: Ext.getCmp("hiddenId").getValue()
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);

                    if (data.ref) {
                        Ext.getCmp("editRef").setValue(data.ref);
                    }

                    Ext.getCmp("editBizUserId").setValue(data.bizUserId);
                    Ext.getCmp("editBizUser").setValue(data.bizUserName);
                    if (data.bizDT) {
                        Ext.getCmp("editBizDT").setValue(data.bizDT);
                    }
                    if (data.warehouseId) {
                    	Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    	Ext.getCmp("editWarehouse").setValue(data.warehouseName);
                    }

                    var store = me.getGoodsGrid().getStore();
                    store.removeAll();
                    if (data.items) {
                        store.add(data.items);
                    }
                    if (store.getCount() == 0) {
                        store.add({});
                    }

                    if (data.billStatus && data.billStatus == 1000) {
                    	me.setBillReadonly();
                    }
                } else {
                    ERP.MsgBox.showInfo("网络错误")
                }
            }
        });
    },
    
    onOK: function () {
        var me = this;
        Ext.getBody().mask("正在保存中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/InvCheck/editICBill",
            method: "POST",
            params: {jsonStr: me.getSaveData()},
            callback: function (options, success, response) {
                Ext.getBody().unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                        ERP.MsgBox.showInfo("成功保存数据", function () {
                            me.close();
                            me.getParentForm().refreshMainGrid(data.id);
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
            var store = me.getGoodsGrid().getStore();
            if (store.getCount() == 0) {
                store.add({});
            }
            me.getGoodsGrid().focus();
            me.__cellEditing.startEdit(0, 1);
        }
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
        var modelName = "ERPICBillDetail_EditForm";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount", "goodsPrice", "goodsMoney", "goodsCountBefore", "position"]
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
                    sortable: false, editor: {xtype: "jyerp_goodsfield", parentCmp: me}},
                {header: "商品名称", dataIndex: "goodsName", menuDisabled: true, sortable: false, width: 200},
                {header: "规格型号", dataIndex: "goodsSpec", menuDisabled: true, sortable: false, width: 200},
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
                {header: "仓位", dataIndex: "position", menuDisabled: true, sortable: false, width: 60},
                {header: "盘点前库存数量", dataIndex: "goodsCountBefore", menuDisabled: true, sortable: false, width: 100},
                {header: "盘点后库存数量", dataIndex: "goodsCount", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: true,
                        hideTrigger: true}
                },
                {header: "盘点差异", dataIndex: "goodsCountDiff", menuDisabled: true, sortable: false, width: 100},
                {header: "最新采购价", dataIndex: "goodsPrice", menuDisabled: true, sortable: false, width: 100},

                /*
                {
					header : "盘点后库存金额",
					dataIndex : "goodsMoney",
					menuDisabled : true,
					sortable : false,
					align : "right",
					xtype : "numbercolumn",
					width : 100,
					editor : {
						xtype : "numberfield",
						hideTrigger : true
					}
				}, 
                */

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
        if (e.colIdx == 7) {
            me.calcDiff();
            if (!me.__canEditGoodsPrice) {
                var store = me.getGoodsGrid().getStore();
                if (e.rowIdx == store.getCount() - 1) {
                    store.add({});
                }
                e.rowIdx += 1;
                me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
                me.__cellEditing.startEdit(e.rowIdx, 1);
            }
        }
    },
    calcDiff : function() {
        var me = this;
        var item = me.getGoodsGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        var goods = item[0];
        var g_before = parseFloat(goods.get("goodsCountBefore"));
        var g_after = parseFloat(goods.get("goodsCount"));
        goods.set("goodsCountDiff", g_after - g_before);
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
        goods.set("unitName", data.unitNamePW);
        goods.set("goodsSpec", data.spec);
        goods.set("goodsCountBefore", data.goodsCountBefore);
        goods.set("goodsPrice", data.lastBuyPrice);
        goods.set("position", data.position);
    },
    
    __setWarehouseId: function (data) {
    	Ext.getCmp("editWarehouseId").setValue(data.id);
    },

    __getWarehouseId:function(){
        return Ext.getCmp("editWarehouseId").getValue();
    },
    
    getSaveData: function () {
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
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
                goodsCount: item.get("goodsCount"),
                goodsMoney: item.get("goodsMoney"),
                goodsCountBefore: item.get("goodsCountBefore"),
                goodsPosition: item.get("position"),
            });
        }

        return Ext.JSON.encode(result);
    },

    setBillReadonly: function() {
    	var me = this;
    	me.__readonly = true;
    	me.setTitle("查看盘点单");
    	Ext.getCmp("buttonSave").setDisabled(true);
    	Ext.getCmp("buttonCancel").setText("关闭");
    	Ext.getCmp("editBizDT").setReadOnly(true);
    	Ext.getCmp("editWarehouse").setReadOnly(true);
    	Ext.getCmp("editBizUser").setReadOnly(true);
    	Ext.getCmp("columnActionDelete").hide();
    },
    onQueryGoods: function() {
    	alert(9);
    }
});