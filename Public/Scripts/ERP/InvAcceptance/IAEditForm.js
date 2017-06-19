
Ext.define("ERP.Purchase.IAEditForm", {
    extend: "Ext.window.Window",

    config: {
        parentForm: null,
        entity: null
    },

    initComponent: function () {
        var me = this;
        me.__readOnly = false;
        var entity = me.getEntity();
        this.adding = entity == null;
        //商品种类
        var goodsType = Ext.regModel('ERPGoodsType', {
            fields: ["id", "name"]
        });
        var goodsTypeStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsType',
            data: [{"id": "0", "name": "正常商品"}, {"id": "1", "name": "赠品"}]
        });
        var goodsTypeData = ['正常商品', '赠品'];
        me.goodsTypeData = goodsTypeData;
        me.goodsTypeStore = goodsTypeStore;
        Ext.apply(me, {
            title: entity == null ? "新建验收单" : "编辑验收单",
            modal: true,
            onEsc: Ext.emptyFn,
            maximized: true,
            width: 1000,
            height: 600,
            layout: "border",
            defaultFocus: "editSupplier",
            tbar: ["-", {
                text: "保存入库",
                id: "buttonSave",
                iconCls: "ERP-button-ok",
                handler: me.onOK,
                scope: me
            }, "-", {
                text: "取消",
                id: "buttonCancel",
                iconCls: "ERP-button-cancel",
                handler: function () {
                    if (me.__readonly) {
                        me.close();
                        return;
                    }

                    ERP.MsgBox.confirm("请确认是否取消当前操作？", function () {
                        me.close();
                    });
                },
                scope: me
            }],
            items: [{
                region: "center",
                layout: "fit",
                border: 0,
                bodyPadding: 10,
                items: [me.getGoodsGrid()]
            }, {
                region: "north",
                id: "editForm",
                layout: {
                    type: "table",
                    columns: 2
                },
                height: 120,
                bodyPadding: 10,
                border: 0,
                items: [{
                    xtype: "hidden",
                    id: "hiddenId",
                    name: "id",
                    value: entity == null ? null : entity.get("id")
                }, {
                    id: "editRef",
                    labelWidth: 60,
                    labelAlign: "right",
                    labelSeparator: "",
                    fieldLabel: "单号",
                    xtype: "displayfield",
                    value: "<span style='color:red'>保存后自动生成</span>"
                }, {
                    id: "editBizDT",
                    fieldLabel: "业务日期",
                    labelWidth: 60,
                    labelAlign: "right",
                    labelSeparator: "",
                    allowBlank: false,
                    blankText: "没有输入业务日期",
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
                        id: "editPWBillRef",
                        colspan: 2,
                        width: 430,
                        labelWidth: 60,
                        labelAlign: "right",
                        labelSeparator: "",
                        xtype: "jyerp_purchasefield",
                        parentCmp: me,
                        fieldLabel: "采购单号",
                        allowBlank: false,
                        blankText: "没有输入采购单号",
                        editable: false,
                        beforeLabelTextTpl: ERP.Const.REQUIRED,
                        listeners: {
                            specialkey: {
                                fn: me.onEditSupplierSpecialKey,
                                scope: me
                            }
                        }
                    }, {
                        xtype: "hidden",
                        id: "editPWBillId",
                        name: "PWBillId"
                    },
                    {
                        xtype: "hidden",
                        id: "editSupplierId",
                        name: "supplierId"
                    },

                    {
                        id: "editSupplier",
                        colspan: 2,
                        width: 430,
                        labelWidth: 60,
                        xtype: "displayfield",
                        beforeLabelTextTpl: ERP.Const.REQUIRED,
                        fieldLabel: "供应商",
                        value: ""
                    }, {
                        xtype: "hidden",
                        id: "editWarehouseId",
                        name: "warehouseId"
                    }, {
                        id: "editWarehouse",
                        labelWidth: 60,
                        labelAlign: "right",
                        labelSeparator: "",
                        fieldLabel: "入库仓库",
                        xtype: "jyerp_warehousefield",
                        parentCmp: me,
                        fid: "2001",
                        listeners: {
                            specialkey: {
                                fn: me.onEditWarehouseSpecialKey,
                                scope: me
                            }
                        }
                    }, {
                        xtype: "hidden",
                        id: "editBizUserId",
                        name: "bizUserId"
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
            url: ERP.Const.BASE_URL + "Home/InvAcceptance/iaBillInfo",
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

                    Ext.getCmp("editSupplierId").setValue(data.supplierId);
                    Ext.getCmp("editSupplier").setValue(data.supplierName);
                    Ext.getCmp("editPWBillRef").setValue(data.pw_billid);
                    Ext.getCmp("editWarehouseId").setValue(data.warehouseId);
                    Ext.getCmp("editWarehouse").setValue(data.warehouseName);
                    Ext.getCmp("editBizUserId").setValue(data.bizUserId);
                    //Ext.getCmp("editBizUser").setValue(data.bizUserName);
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

                    if (data.billStatus && (data.billStatus != 0)) {
                        me.setBillReadonly();
                    }
                }
            }
        });
    },

    __getPWBillDetail: function (id) {
        var me = this;

        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Purchase/pwBillInfo",
            params: {
                id: id
            },
            method: "POST",
            callback: function (options, success, response) {
                el.unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    var store = me.getGoodsGrid().getStore();
                    store.removeAll();
                    if (data.items) {
                        store.add(data.items);
                    }
                    if (store.getCount() == 0) {
                        store.add({});
                    }
                    /*
                     if (data.billStatus && data.billStatus !=0) {
                     me.setBillReadonly();
                     }
                     */
                }
            }
        });
    },
    // private
    onOK: function () {
        var me = this;
        //disable掉按钮
        Ext.getCmp("buttonSave").disable();
        Ext.getBody().mask("正在提交中...");
        Ext.Ajax.request({
//			url : ERP.Const.BASE_URL + "Home/InvAcceptance/commitIABill",
            url: ERP.Const.BASE_URL + "Home/InvAcceptance/editIABill",
            method: "POST",
            params: {
                jsonStr: me.getSaveData()
            },
            callback: function (options, success, response) {
                Ext.getBody().unmask();

                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.success) {
                        ERP.MsgBox.showInfo("成功保存数据", function () {
                            me.close();
                            me.getParentForm().refreshIABillGrid(data.id);
                        });
                    } else {
                        ERP.MsgBox.showInfo(data.msg);
                        Ext.getCmp("buttonSave").enable();
                    }
                }
            }
        });

    },

    onEditBizDTSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editSupplier").focus();
        }
    },

    onEditSupplierSpecialKey: function (field, e) {
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
    // SupplierField回调此方法
    __setSupplierInfo: function (data) {
        Ext.getCmp("editSupplierId").setValue(data.id);
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
        Ext.define("ERPPWBillDetail_EditForm", {
            extend: "Ext.data.Model",
            fields: ["id", "goodsId", "goodsCode", "goodsName",
                "goodsSpec","unitName", "goodsCount", "goodsMoney",
                 "goodsPrice", "goodsType", "_goodsCount", "_goodsPrice"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad: false,
            model: "ERPPWBillDetail_EditForm",
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
                Ext.create("Ext.grid.RowNumberer", {
                    text: "序号",
                    width: 30
                }),
                {
                    header: "商品编码",
                    dataIndex: "goodsCode",
                    menuDisabled: true,
                    sortable: false,
                    editor: {
                        xtype: "jyerp_goodsfield",
                        parentCmp: me
                    }
                },
                {
                    header: "商品名称",
                    dataIndex: "goodsName",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                },
                {
                    header: "规格型号",
                    dataIndex: "goodsSpec",
                    menuDisabled: true,
                    sortable: false,
                    width: 200
                },
                {
                    header: "入库数量",
                    dataIndex: "goodsCount",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 100,
                    editor: {
                        xtype: "numberfield",
                        allowDecimals: true,
                        hideTrigger: true,
                        listeners: {
                            change: function (field, newValue, oldValue) {
                                //防止产品数量大于采购单的数量
                                oldValue = parseFloat(oldValue);
                                newValue = parseFloat(newValue);

                                var item = me.getGoodsGrid().getSelectionModel().getSelection();
                                if (item == null || item.length != 1) {
                                    return;
                                }
                                var goods = item[0];
                                var goodsCount = parseFloat(goods.get("_goodsCount"));
                                if (parseInt(newValue) <= 0) {
                                    field.setValue(goodsCount);
                                }
                                if (newValue > goodsCount) {
                                    field.setValue(goodsCount);
                                }

                            },
                            blur: function (field, newValue, oldValue) {
                                oldValue = parseFloat(oldValue);
                                newValue = parseFloat(newValue);

                                var item = me.getGoodsGrid().getSelectionModel().getSelection();
                                if (item == null || item.length != 1) {
                                    return;
                                }
                                var goods = item[0];
                                var goodsCount = parseFloat(goods.get("_goodsCount"));
                                if (parseInt(newValue) <= 0) {
                                    field.setValue(goodsCount);
                                }
                                if (newValue > goodsCount) {
                                    field.setValue(goodsCount);
                                }
                            }
                        }
                    },

                },
                {
                    header: "单位",
                    dataIndex: "unitName",
                    menuDisabled: true,
                    sortable: false,
                    width: 60
                },
                {
                    header: "采购单价",
                    dataIndex: "goodsPrice",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 100,
                    editor: {
                        xtype: "numberfield",
                        hideTrigger: true,
                        listeners: {
                            change: function (field, newValue, oldValue) {


                            },
                            blur: function (field, newValue, oldValue) {

                            }
                        }
                    },

                },
                {
                    header: "采购金额",
                    dataIndex: "goodsMoney",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    xtype: "numbercolumn",
                    width: 120
                },
                {
                    header: "商品种类",
                    dataIndex: "goodsType",
                    menuDisabled: true,
                    sortable: false,
                    align: "right",
                    width: 120,

                    editor: new Ext.form.field.ComboBox({
                        //typeAhead:true,
                        //readOnly:true,
                        queryMode: 'local',
                        //id:'editGoodsType',
                        triggerAction: 'all',
                        valueField: 'id',
                        displayField: 'name',
                        store: me.goodsTypeStore,
                        //lazyRender:true
                    }),
                    renderer: function (value) {
                        if (value === "") {
                            return me.goodsTypeData[0];
                        } else {
                            if (value == "0") {
                                return me.goodsTypeData[value];
                            } else if (value == "1") {
                                //var item = me.getGoodsGrid().getSelectionModel().getSelection();
                                //var goods = item[0];
                                //goods.set("goodsPrice", 0);
                                //goods.set("goodsMoney", 0);
                                //goods.set("goodsType", "赠品");
                                return me.goodsTypeData[value];
                            } else {

                            }
                        }

                    }
                },
                {
                    header: "",
                    id: "columnActionDelete",
                    align: "center",
                    menuDisabled: true,
                    width: 50,
                    xtype: "actioncolumn",
                    items: [{
                        icon: ERP.Const.BASE_URL
                        + "Public/Images/icons/delete.png",
                        handler: function (grid, row) {
                            var store = grid.getStore();
                            store.remove(store.getAt(row));
                            if (store.getCount() == 0) {
                                store.add({});
                            }
                        },
                        scope: me
                    }]
                }],
            store: store,
            listeners: {
                cellclick: function () {
                    return !me.__readonly;
                }
            }
        });

        return me.__goodsGrid;
    },

    __setGoodsInfo: function (data) {
        //如果没有选择供应商，则无法添加商品数据
        if (Ext.getCmp("editSupplierId").getValue() == "") {
            ERP.MsgBox.showInfo("请先选择一个供应商!");
            return;
        }
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

    __setPurchaseInfo: function (data) {
        //alert(data.supplier_id)
        Ext.getCmp("editPWBillRef").setValue(data.ref);
        Ext.getCmp("editPWBillId").setValue(data.id);
        Ext.getCmp("editSupplierId").setValue(data.supplier_id);
        Ext.getCmp("editSupplier").setValue(data.supplier);
        Ext.getCmp("editWarehouseId").setValue(data.warehouse_id);
        Ext.getCmp("editWarehouse").setValue(data.warehouse);
        //设置成只读
        //Ext.getCmp("editPWBillRef").setReadOnly(true);
        //读取采购单的明细列表
        this.__getPWBillDetail(data.id);
    },

    cellEditingAfterEdit: function (editor, e) {
        var me = this;

        if (me.__readonly) {
            return;
        }

        if (e.colIdx == 6) {
            me.calcMoney();

            var store = me.getGoodsGrid().getStore();
            if (e.rowIdx == store.getCount() - 1) {
                store.add({});
            }
            e.rowIdx += 1;
            me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
            me.__cellEditing.startEdit(e.rowIdx, 1);
        } else if (e.colIdx == 4) {
            me.calcMoney();
        } else if (e.colIdx == 8) {
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
        var goodsType = goods.get("goodsType");
        if (goodsType == 0 || goodsType == "") {

        } else {
            goods.set("goodsPrice", 0);
        }
        goods.set("goodsMoney", goods.get("goodsCount")
            * goods.get("goodsPrice"));
    },

    getSaveData: function () {
        var result = {
            id: Ext.getCmp("hiddenId").getValue(),
            bizDT: Ext.Date
                .format(Ext.getCmp("editBizDT").getValue(), "Y-m-d"),
            supplierId: Ext.getCmp("editSupplierId").getValue(),
            warehouseId: Ext.getCmp("editWarehouseId").getValue(),
            bizUserId: Ext.getCmp("editBizUserId").getValue(),
            pwbillId: Ext.getCmp("editPWBillId").getValue(),
            items: []
        };

        var store = this.getGoodsGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id: item.get("id"),
                goodsId: item.get("goodsId"),
                goodsCount: item.get("goodsCount"),
                goodsPrice: item.get("goodsPrice"),
                goodsType: item.get("goodsType")
            });
        }

        return Ext.JSON.encode(result);
    },

    setBillReadonly: function () {
        var me = this;
        me.__readonly = true;
        me.setTitle("查看采购入库单");
        Ext.getCmp("buttonSave").setDisabled(true);
        Ext.getCmp("buttonCancel").setText("关闭");
        Ext.getCmp("editBizDT").setReadOnly(true);
        Ext.getCmp("editSupplier").setReadOnly(true);
        Ext.getCmp("editWarehouse").setReadOnly(true);
        Ext.getCmp("columnActionDelete").hide();
    }
});