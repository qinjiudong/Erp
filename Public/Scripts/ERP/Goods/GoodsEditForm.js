// 商品 - 新建或编辑界面
Ext.define("ERP.Goods.GoodsEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        me.__readOnly = false;
        Ext.define("ERPGoodsUnit", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });
        //新建供应商grid
        Ext.define("GoodsSupplier", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", "contact01", 'tel01', "mobile01"]
        });
        //同一商品不同规格
        Ext.define("ERPGoodsSameUnit", {
            extend: "Ext.data.Model",
            fields: ["id", "name"]
        });
        var goodsSameUnitStore = Ext.create("Ext.data.Store", {
            model: "ERPGoodsSameUnit",
            autoLoad: false,
            data: []
        });
        var goodsSupplierStore = Ext.create("Ext.data.Store", {
            model: "GoodsSupplier",
            autoLoad: true,
            pageSize: 5,
            data:[]
        });
        var unitStore = Ext.create("Ext.data.Store", {
            model: "ERPGoodsUnit",
            autoLoad: false,
            data: []
        });
        me.unitStore = unitStore;
        me.goodsSameUnitStore = goodsSameUnitStore;
        me.adding = entity == null;
        var lastPriceDiasbled = true;
        var buttons = [];
        if (!entity) {
            buttons.push({
                text: "保存并继续新增",
                formBind: true,
                handler: function () {
                    me.onOK(true);
                },
                scope: me
            });
            lastPriceDiasbled = false;
        } else {
            lastPriceDiasbled = true;
        }
        

        buttons.push({
            text: "保存",
            formBind: true,
            iconCls: "ERP-button-ok",
            handler: function () {
                me.onOK(false);
            }, scope: me
        }, {
            text: entity == null ? "关闭" : "取消", handler: function () {
                me.close();
            }, scope: me
        });

        var categoryStore = me.getParentForm().categoryTree.getStore();
        var selectedCategory = me.getParentForm().categoryTree.getSelectionModel().getSelection();
        var defaultCategoryId = null;
        var defaultCategoryName = null;
        if (selectedCategory != null && selectedCategory.length > 0) {
            defaultCategoryId = selectedCategory[0].get("id");
            defaultCategoryName = selectedCategory[0].get("name");
        }

        //商品状态
        var status = Ext.regModel('ERPGoodsStatus', {
            fields: ["id", "name"]
        });
        var statusStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsStatus',
            data: [{"id":"1","name":"正常商品"},{"id":"0", "name":"尚未生效"}]
        });
        me.statusStore = statusStore;
        //储存属性
        var storage = Ext.regModel('ERPGoodsStorage', {
            fields: ["id", "name"]
        });
        var storageStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsStorage',
            data: [{"id":"0","name":"常温储存"},{"id":"1", "name":"冷藏储存"}]
        });
        me.storageStore = storageStore;
        //散装属性
        var bulk = Ext.regModel('ERPGoodsBulk', {
            fields: ["id", "name"]
        });
        var bulkStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsBulk',
            data: [{"id":"0","name":"计重"},{"id":"1", "name":"计个"}]
        });
         me.bulkStore = bulkStore;
        //经营方式
        var mode = Ext.regModel('ERPGoodsMode', {
            fields: ["id", "name"]
        });
        var modeStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsMode',
            data: [{"id":"0","name":"经销"},{"id":"1", "name":"联营"}]
        });
        me.modeStore = modeStore;

        //负库存销售
        var oversold = Ext.regModel('ERPGoodsOversold', {
            fields: ["id", "name"]
        });
        var oversoldStore = Ext.create('Ext.data.Store', {
            model: 'ERPGoodsOversold',
            data: [{"id":"0","name":"不允许"},{"id":"1", "name":"允许"}]
        });
        me.oversoldStore = oversoldStore;
        var defaultStorageId = "0";
        var defaultBulkId = null;
        var defaultModeId = "0";
        Ext.apply(me, {
            title: entity == null ? "新增商品" : "编辑商品",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 800,
            height: '100%',
            layout: "fit",
            items: [ 
                {
                    id: "editForm",
                    xtype: "form",
                    layout : {
                        type : "table",
                        columns : 2
                    },
                    height: "100%",
                    width:"100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 80,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "id",
                            value: entity == null ? null : entity.get("id")
                        },
                        {
                            title: '商品基本信息',
                            xtype:"html",
                            html: '',
                            colspan: 2,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: "hidden",
                            name: "category_id",
                            id: "editCategoryId",
                            value: defaultCategoryId,
                        }, 
                        {
                            id: "editCategory",
                            //xtype: "combo",
                            xtype : "jyerp_goods_category_field",
                            parentCmp : me,
                            fieldLabel: "商品分类",
                            allowBlank: false,
                            blankText: "没有输入商品分类",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            //store: categoryStore,
                            queryMode: "local",
                            editable: false,
                            value: defaultCategoryName,
                            name: "category_name",
                            width:"500",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editCode",
                            fieldLabel: "商品编码",
                            allowBlank: false,
                            blankText: "没有输入商品编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity == null ? null : entity.get("code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editBarCode",
                            fieldLabel: "商品条码",
                            allowBlank: true,
                            //blankText: "没有输入商品编码",
                            //beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "barCode",
                            //colspan: 2,
                            value: entity == null ? null : entity.get("barCode"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editSameUnitCode",
                            xtype: "combo",
                            fieldLabel: "规格类型",
                            //allowBlank: false,
                            //blankText: "没有输入计量单位",
                            //beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: goodsSameUnitStore,
                            queryMode: "local",
                            editable: true,
                            name: "same_spec_id",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        }, 
                        {
                            id: "editName",
                            fieldLabel: "商品名称",
                            allowBlank: false,
                            blankText: "没有输入品名",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity == null ? null : entity.get("name"),
                            colspan: 2,
                            width:620,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editNumber",
                            fieldLabel: "商品货号",
                            name: "number",
                            value: entity == null ? null : entity.get("number"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editStatus",
                            xtype: "combo",
                            fieldLabel: "商品状态",
                            allowBlank: false,
                            blankText: "没有选择商品状态",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: statusStore,
                            queryMode: "local",
                            editable: false,
                            value: "1",
                            name: "status",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editPlace",
                            fieldLabel: "产地",
                            name: "place",
                            value: entity == null ? null : entity.get("place"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editSpec",
                            fieldLabel: "规格型号",
                            name: "spec",
                            value: entity == null ? null : entity.get("spec"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editBrand",
                            fieldLabel: "商品品牌",
                            name: "brand",
                            value: entity == null ? null : entity.get("brand"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editLife",
                            fieldLabel: "保质期",
                            name: "life",
                            value: entity == null ? null : entity.get("life"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editSellTax",
                            fieldLabel: "销项税率",
                            name: "selltax",
                            value: entity == null ? null : entity.get("selltax"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editBuyTax",
                            fieldLabel: "进项税率",
                            name: "buytax",
                            value: entity == null ? null : entity.get("life"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },

                        {
                            id: "editUnit",
                            xtype: "combo",
                            fieldLabel: "计量单位",
                            allowBlank: false,
                            blankText: "没有输入计量单位",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: unitStore,
                            queryMode: "local",
                            editable: true,
                            name: "unit_id",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        }, 
                        {
                            id: "editBulk",
                            xtype: "combo",
                            fieldLabel: "散装属性",
                            //allowBlank: false,
                            //blankText: "没有选择散装属性",
                            //beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: bulkStore,
                            queryMode: "local",
                            editable: false,
                            value: "1",
                            name: "bulk",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editStorage",
                            xtype: "combo",
                            fieldLabel: "储存属性",
                            allowBlank: false,
                            blankText: "没有选择储存属性",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: storageStore,
                            queryMode: "local",
                            editable: false,
                            value: defaultStorageId,
                            name: "storage",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editOversold",
                            xtype: "combo",
                            fieldLabel: "负库存销售",
                            valueField: "id",
                            displayField: "name",
                            store: oversoldStore,
                            queryMode: "local",
                            editable: false,
                            value: "0",
                            name: "oversold",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },

                        {
                            fieldLabel: "市场价",
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "shop_price",
                            id: "editShopPrice",
                            value: entity == null ? 0 : entity.get("shopPrice"),
                            //colspan: 2,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
						{
                            fieldLabel: "促销数量",
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "promote_num",
                            id: "editPromoteNum",
                            value: entity == null ? 0 : entity.get("promoteNum"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            fieldLabel: "销售价(计重商品为公斤价)",
                            allowBlank: false,
                            blankText: "没有输入销售价",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "salePrice",
                            id: "editSalePrice",
                            value: entity == null ? null : entity.get("salePrice"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            fieldLabel: "促销价",
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "promote_price",
                            id: "editPromotePrice",
                            value: entity == null ? 0 : entity.get("promotePrice"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            fieldLabel: "促销开始时间",
                            xtype: "datefield",
                            format: "Y-m-d H:i:s",
                            name: "promote_begin_time",
                            id: "editPromoteBeginTime",
                            value: entity == null ? null : entity.get("promoteBeginTime"),
                        },
                        {
                            fieldLabel: "促销结束时间",
                            xtype: "datefield",
                            format: "Y-m-d H:i:s",
                            name: "promote_end_time",
                            id: "editPromoteEndTime",
                            value: entity == null ? null : entity.get("promoteEndTime")
                        },
                        {
                            fieldLabel: "计重转换系数",
                            allowBlank: true,
                            xtype: "hidden",
                            hideTrigger: true,
                            name: "convert",
                            id: "editConvert",
                            value: entity == null ? null : entity.get("convert"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        
                        {
                            fieldLabel: "最后进价",
                            allowBlank: true,
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "lastBuyPrice",
                            id: "editLastBuyPrice",
                            value: entity == null ? null : entity.get("lastBuyPrice"),
                            readOnly:lastPriceDiasbled,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editGross",
                            fieldLabel: "毛利率",
                            name: "gross",
                            value: entity == null ? null : entity.get("gross"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            fieldLabel: "最低售价",
                            allowBlank: true,
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "minSalePrice",
                            id: "editmMinSalePrice",
                            value: entity == null ? null : entity.get("minSalePrice"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            fieldLabel: "批发价",
                            allowBlank: true,
                            xtype: "numberfield",
                            hideTrigger: true,
                            name: "wholeSalePrice",
                            id: "editmWholeSalePrice",
                            value: entity == null ? null : entity.get("wholeSalePrice"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSalePriceSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editRebateRate",
                            fieldLabel: "返点率",
                            name: "rebateRate",
                            value: entity == null ? null : entity.get("rebateRate"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editMode",
                            xtype: "combo",
                            fieldLabel: "经营方式",
                            allowBlank: false,
                            blankText: "请选择供应商确定经营方式",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            valueField: "id",
                            displayField: "name",
                            store: modeStore,
                            queryMode: "local",
                            editable: false,
                            value: defaultModeId,
                            name: "mode",
                            readOnly:true,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editBaseCode",
                            fieldLabel: "基础商品编码",
                            name: "baseCode",
                            value: entity == null ? null : entity.get("baseCode"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editPackRate",
                            fieldLabel: "包装率",
                            xtype: "numberfield",
                            name: "packRate",
                            value: entity == null ? null : entity.get("packRate"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        /*
                        {
                            title: '供货商信息',
                            xtype:"html",
                            html: '<hr>',
                            colspan: 2,
                            listeners: {
                                specialkey: {
                                    fn: me.onEditSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        */
                        {
                            title: '供货商信息',
                            xtype:"panel",
                            html: '',
                            colspan: 2,
                            items:[{
                                region : "center",
                                layout : "fit",
                                border : 0,
                                bodyPadding : 10,
                                items : [ me.getSupplierGrid()]
                            }
                            ],
                            listeners: {
                                specialkey: {
                                    fn: me.__addNewRow,
                                    scope: me
                                }
                            }
                        },
                        

                    ],
                    buttons: buttons
                }],
            listeners: {
                show: {
                    fn: me.onWndShow,
                    scope: me
                },
                close: {
                    fn: me.onWndClose,
                    scope: me
                }
            }
        });

        me.callParent(arguments);

        me.__editorList = ["editCategory", "editCode", "editName", "editSpec",
            "editUnit", "editSalePrice"];
    },
    onWndShow: function () {
        var me = this;
        var editCode = Ext.getCmp("editCode");
        editCode.focus();
        editCode.setValue(editCode.getValue());

        var el = me.getEl();
        var unitStore = me.unitStore;
        var statusStore = me.statusStore;
        var storageStore = me.storageStore;
        var bulkStore = me.bulkStore;
        var oversoldStore = me.oversoldStore;
        var goodsSameUnitStore = me.goodsSameUnitStore;
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url: ERP.Const.BASE_URL + "Home/Goods/goodsInfo",
            params: {
            	id: me.adding ? null : me.getEntity().get("id")
            },
            method: "POST",
            callback: function (options, success, response) {
                unitStore.removeAll();
                goodsSameUnitStore.removeAll();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    if (data.units) {
                    	unitStore.add(data.units);
                    }
                    if(data.sameunits){
                        goodsSameUnitStore.add(data.sameunits);
                    }
                    if(data.status_arr){
                        statusStore.removeAll();
                        statusStore.add(data.status_arr);
                    }
                    if (!me.adding) {
                    	// 编辑商品信息
                        Ext.getCmp("editCategoryId").setValue(data.category_id);
                        Ext.getCmp("editCategory").setValue(data.category_name);
                        Ext.getCmp("editCode").setValue(data.code);
                        Ext.getCmp("editBarCode").setValue(data.barcode);
                        Ext.getCmp("editName").setValue(data.name);
                        Ext.getCmp("editSpec").setValue(data.spec);
                        Ext.getCmp("editUnit").setValue(data.unit_id);
                        Ext.getCmp("editSameUnitCode").setValue(data.same_spec_id);
                        Ext.getCmp("editSalePrice").setValue(data.sale_price);
                        Ext.getCmp("editConvert").setValue(data.convert);
                        Ext.getCmp("editNumber").setValue(data.number);
                        Ext.getCmp("editStatus").setValue(data.status);
                        Ext.getCmp("editPlace").setValue(data.place);
                        Ext.getCmp("editBrand").setValue(data.brand);
                        Ext.getCmp("editLife").setValue(data.life);
                        Ext.getCmp("editSellTax").setValue(data.selltax);
                        Ext.getCmp("editBuyTax").setValue(data.buytax);
                        Ext.getCmp("editBulk").setValue(data.bulk);
                        Ext.getCmp("editStorage").setValue(data.storage);
                        Ext.getCmp("editOversold").setValue(data.oversold);
                        Ext.getCmp("editLastBuyPrice").setValue(data.lastbuyprice);
                        Ext.getCmp("editGross").setValue(data.gross);
                        Ext.getCmp("editmMinSalePrice").setValue(data.minsaleprice);
                        Ext.getCmp("editmWholeSalePrice").setValue(data.wholesaleprice);
                        Ext.getCmp("editRebateRate").setValue(data.rebaterate);
                        Ext.getCmp("editMode").setValue(data.mode);
                        Ext.getCmp("editPromotePrice").setValue(data.promote_price);
                        Ext.getCmp("editPromoteBeginTime").setValue(data.promote_begin_time);
                        Ext.getCmp("editPromoteEndTime").setValue(data.promote_end_time);
                        Ext.getCmp("editShopPrice").setValue(data.shop_price);
                        Ext.getCmp("editBaseCode").setValue(data.basecode);
                        Ext.getCmp("editPackRate").setValue(data.packrate);
						Ext.getCmp("editPromoteNum").setValue(data.promote_num);
                        var store = me.getSupplierGrid().getStore();
                        store.removeAll();
                        if (data.s_list) {
                            store.add(data.s_list);
                        }
                        if (store.getCount() == 0) {
                            store.add({});
                        }
                    } else {
                    	// 新增商品
                        if (unitStore.getCount() > 0) {
                            var unitId = unitStore.getAt(0).get("id");
                            Ext.getCmp("editUnit").setValue(unitId);
                        }
                        var store = me.getSupplierGrid().getStore();
                        store.removeAll();
                        if (store.getCount() == 0) {
                            store.add({});
                        }
                    }
                }

                el.unmask();
            }
        });
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Goods/editGoods",
            method: "POST",
            params : {
                jsonStr : me.getSaveData()
            },
            success: function (form, action) {
                el.unmask();
                me.__lastId = action.result.id;
                me.getParentForm().__lastId = me.__lastId;

                ERP.MsgBox.tip("数据保存成功");
                me.focus();

                if (thenAdd) {
                    me.clearEdit();
                } else {
                    me.close();
                    me.getParentForm().freshGoodsGrid();
                }
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    Ext.getCmp("editCode").focus();
                });
            }
        });
    },
    onEditSpecialKey: function (field, e) {
        if (e.getKey() === e.ENTER) {
            var me = this;
            var id = field.getId();
            for (var i = 0; i < me.__editorList.length; i++) {
                var editorId = me.__editorList[i];
                if (id === editorId) {
                    var edit = Ext.getCmp(me.__editorList[i + 1]);
                    edit.focus();
                    edit.setValue(edit.getValue());
                }
            }
        }
    },
    onEditSalePriceSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                var me = this;
                me.onOK(me.adding);
            }
        }
    },
    clearEdit: function () {
        Ext.getCmp("editCode").focus();

        var editors = [Ext.getCmp("editCode"), Ext.getCmp("editName"), Ext.getCmp("editSpec"),
            Ext.getCmp("editSalePrice")];
        for (var i = 0; i < editors.length; i++) {
            var edit = editors[i];
            edit.setValue(null);
            edit.clearInvalid();
        }
    },
    onWndClose: function () {
        var me = this;
        me.getParentForm().__lastId = me.__lastId;
        me.getParentForm().freshGoodsGrid();
    },
    getSupplierGrid : function() {
        var me = this;
        if (me.__supplierGrid) {
            return me.__supplierGrid;
        }
        Ext.define("ERPGoodsSupplier_EditForm", {
            extend : "Ext.data.Model",
            fields : [ "id", "supplierId", "supplierCode", "supplierName", "supplierAddress",  "supplierContact01",
                    "supplierTel01", "supplierMobile01"]
        });
        var store = Ext.create("Ext.data.Store", {
            autoLoad : false,
            model : "ERPGoodsSupplier_EditForm",
            data : []
        });

        me.__cellEditing = Ext.create("ERP.UX.CellEditing", {
            clicksToEdit : 1,
            listeners : {
                edit : {
                    fn : me.cellEditingAfterEdit,
                    scope : me
                }
            }
        });

        me.__supplierGrid = Ext.create("Ext.grid.Panel", {
            viewConfig: {
                enableTextSelection: true
            },
            plugins : [ me.__cellEditing ],
            columnLines : true,
            columns : [
                    Ext.create("Ext.grid.RowNumberer", {
                        text : "序号",
                        width : 30
                    }),
                    {
                        header : "供应商编码",
                        dataIndex : "supplierCode",
                        menuDisabled : true,
                        sortable : false,
                        editor : {
                            xtype : "jyerp_supplierfield",
                            parentCmp : me
                        }
                    },
                    {
                        header : "供应商名称",
                        dataIndex : "supplierName",
                        menuDisabled : true,
                        sortable : false,
                        width : 150
                    },
                    {
                        header : "地址",
                        dataIndex : "supplierAddress",
                        menuDisabled : true,
                        sortable : false,
                        width : 150
                    },
                    {
                        header : "联系人",
                        dataIndex : "supplierContact01",
                        menuDisabled : true,
                        sortable : false,
                        align : "right",
                        width : 50,
                        /*
                        editor : {
                            xtype : "numberfield",
                            allowDecimals : false,
                            hideTrigger : true
                        }
                        */
                    },
                    {
                        header : "联系电话",
                        dataIndex : "supplierTel01",
                        menuDisabled : true,
                        sortable : false,
                        width : 80
                    },
                    {
                        header : "联系手机",
                        dataIndex : "supplierMobile01",
                        menuDisabled : true,
                        sortable : false,
                        width : 80
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
                        },
                        {
                            icon : ERP.Const.BASE_URL
                                    + "Public/Images/icons/add.png",
                            handler : function(grid, row) {
                                var store = grid.getStore();
                                me.__addNewRow();
                                /*
                                if (store.getCount() >= 0) {
                                    store.add({});
                                }
                                */
                            },
                            scope : me
                        }
                         ]
                    } ],
            store : store,
            listeners : {
                cellclick: function() {
                    return !me.__readonly;
                },
                celldblclick:function(grid, row){
                    /*
                    var store = grid.getStore();
                    if (store.getCount() >= 0) {
                        store.add({});
                    }
                    */
                }
            }
        });

        return me.__supplierGrid;
    },
    __setSupplierInfo : function(data) {
        var me = this;
        var item = me.getSupplierGrid().getSelectionModel().getSelection();
        if (item == null || item.length != 1) {
            return;
        }
        //查看是否存在
        var store = me.getSupplierGrid().getStore();
        for(var i = 0 ; i< store.getCount() ; i++){
            if(store.getAt(i).get('supplierCode') == data.code){
                ERP.MsgBox.showInfo("该供应商已经存在了.");
                return false;
            }
        }
        Ext.getCmp("editMode").setValue(data.mode);
        var supplier = item[0];
        supplier.set("supplierId", data.id);
        supplier.set("supplierCode", data.code);
        supplier.set("supplierName", data.name);
        supplier.set("supplierAddress", data.address);
        supplier.set("supplierTel01", data.tel01);
        supplier.set("supplierMobile01", data.mobile01);
        supplier.set("supplierContact01", data.contact01);

    },
    cellEditingAfterEdit : function(editor, e) {
        var me = this;
        
        if (me.__readonly) {
            //return;
        }
        if (e.colIdx == 6) {
            //me.calcMoney();

            var store = me.getSupplierGrid().getStore();
            if (e.rowIdx == store.getCount() - 1) {
                store.add({});
            }
            e.rowIdx += 1;
            me.getSupplierGrid().getSelectionModel().select(e.rowIdx);
            me.__cellEditing.startEdit(e.rowIdx, 1);
        } else if (e.colIdx == 4) {
            //me.calcMoney();
        }
    },
    __addNewRow: function(e){
        var me = this;
        var store = this.getSupplierGrid().getStore();
        var i = store.getCount()-1;
        var supplierCode = store.getAt(i).get("supplierCode");
        if(supplierCode){
            store.add({});
        }
    },
    getSaveData : function() {
        var result = {
            items : []
        };

        var store = this.getSupplierGrid().getStore();
        for (var i = 0; i < store.getCount(); i++) {
            var item = store.getAt(i);
            result.items.push({
                id : item.get("id"),
                supplierId : item.get("supplierId"),
                supplierCode : item.get("supplierCode"),
                supplierName : item.get("supplierName")
            });
        }

        return Ext.JSON.encode(result);
    },
    setParentGoodsCategory: function(data){
        Ext.getCmp("editCategory").setValue(data.name);
        Ext.getCmp("editCategoryId").setValue(data.id);
    }
});