// 损溢单
Ext.define("ERP.Inventory.ITEditForm", {
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

        Ext.apply(me, {title: entity == null ? "新建损溢单" : "编辑损溢单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 1000,
            height: 600,
            layout: "border",
            defaultFocus: "editFromWarehouse",
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
                	ERP.MsgBox.confirm("请确认是否取消当前操作?", function(){
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
                    height: 60,
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
                            id: "editSupplierId"
                        },
                        {
                            xtype: "hidden",
                            id: "editFromWarehouseId",
                            name: "warehouseId"
                        },
                        {
                            id: "editFromWarehouse",
                            fieldLabel: "选择仓库",
                            labelWidth: 60,
                            labelAlign: "right",
                            labelSeparator: "",
                            xtype: "jyerp_warehousefield",
                            callbackFunc: me.__setFromWarehouseId,
                            fid: "2009",
                            allowBlank: false,
                            blankText: "没有输入调出仓库",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditFromWarehouseSpecialKey,
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
            url: ERP.Const.BASE_URL + "Home/Inventory/itBillInfo",
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

                    if (data.bizDT) {
                        Ext.getCmp("editBizDT").setValue(data.bizDT);
                    }
                    if (data.fromWarehouseId) {
                    	Ext.getCmp("editFromWarehouseId").setValue(data.fromWarehouseId);
                    	Ext.getCmp("editFromWarehouse").setValue(data.fromWarehouseName);
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
    
    onOK: function () {
        var me = this;
        Ext.getBody().mask("正在保存中...");
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Inventory/editITBill",
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
            Ext.getCmp("editFromWarehouse").focus();
        }
    },
    
    onEditFromWarehouseSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editToWarehouse").focus();
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
        var modelName = "ERPITBillDetail_EditForm";
        Ext.define(modelName, {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName", "goodsSpec", "unitName", "goodsCount"]
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
                {header: "损溢数量", dataIndex: "goodsCount", menuDisabled: true,
                    sortable: false, align: "right", width: 100,
                    editor: {xtype: "numberfield",
                        allowDecimals: false,
                        hideTrigger: true}
                },
                {header: "单位", dataIndex: "unitName", menuDisabled: true, sortable: false, width: 60},
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
    },
    
    __setFromWarehouseId: function (data) {
    	Ext.getCmp("editFromWarehouseId").setValue(data.id);
    },
    
    __setToWarehouseId: function (data) {
    	Ext.getCmp("editToWarehouseId").setValue(data.id);
    },

    getSaveData: function () {
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date.format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
            fromWarehouseId: Ext.getCmp("editFromWarehouseId").getValue(),
            items: []
        };

        var store = this.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                goodsCount: item.get("goodsCount")
            });
        }

        return Ext.JSON.encode(result);
    },

    setBillReadonly: function() {
    	var me = this;
    	me.__readonly = true;
    	me.setTitle("查看损溢单");
    	Ext.getCmp("buttonSave").setDisabled(true);
    	Ext.getCmp("buttonCancel").setText("关闭");
    	Ext.getCmp("editBizDT").setReadOnly(true);
    	Ext.getCmp("editFromWarehouse").setReadOnly(true);
    	Ext.getCmp("columnActionDelete").hide();
    }
});