Ext.define("ERP.Sale.SiteEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();
        var ref_arr = [];
        me.__ref_arr = ref_arr;
        for(i = 0 ; i<entity.length; i++){
            var item = entity[i];
            ref_arr.push(item.get("ref"));
            
        }
        var buttons = [];

        Ext.define("ERPSiteCategory", {
            extend: "Ext.data.Model",
            fields: ["id", "code", "name", {name: "cnt", type: "int"}]
        });

        var storeCategory = Ext.create("Ext.data.Store", {
                model: "ERPSiteCategory",
                autoLoad: false,
                data: []
        });
        me.__storeArea = storeCategory;
        buttons.push({
            text: "保存",
            formBind: true,
            iconCls: "ERP-button-ok",
            handler: function () {
                me.onOK(false);
            }, scope: this
        }, {
            text: "关闭", handler: function () {
                me.close();
            }, scope: me
        });

        Ext.apply(me, {
            title: "编辑配送信息",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 160,
            layout: "fit",
            items: [
                {
                    id: "editSiteForm",
                    xtype: "form",
                    layout: "form",
                    height: "100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 100,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "ref",
                            id: "ref"
                        },{
                            xtype: "hidden",
                            name: "siteid",
                            id:"siteid"
                        }, {
                            id: "editAreaId",
                            fieldLabel: "送货区域",
                            xtype:"combo",
                            queryMode : "local",
                            editable : false,
                            valueField : "id",
                            displayField: "name",
                            name: "areaid",
                            store : storeCategory,
                        }, {
                            id: "editSiteName",
                            fieldLabel: "站点",
                            xtype: "jyerp_sitefield",
                            parentCmp: me,
                            name: "sitename",
                            valueField : "id",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editAddressDetail",
                            fieldLabel: "具体地址",
                            xtype: "textfield",
                            parentCmp: me,
                            name: "address_detail",
                            valueField : "id",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ],
                    buttons: buttons,
                }
            ],
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
    },
    // private
    onOK: function (thenAdd) {
        var me = this;
        var f = Ext.getCmp("editSiteForm");
        var el = f.getEl();
        el.mask(ERP.Const.SAVING);
        Ext.getCmp("ref").setValue(me.__ref_arr.join(","));
        f.submit({
            url: ERP.Const.BASE_URL + "Home/Sale/editSite",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                ERP.MsgBox.tip("数据保存成功");
                me.focus();
                me.__lastId = action.result.id;
                me.close();
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    //Ext.getCmp("editCode").focus();
                });
            }
        });
    },
    onEditCodeSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var editName = Ext.getCmp("editName");
            editName.focus();
            editName.setValue(editName.getValue());
        }
    },
    onEditNameSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                var me = this;
                Ext.getCmp("editCode").focus();
                me.onOK(me.adding);
            }
        }
    },
    onWndClose: function () {
        var me = this;
        if (me.__lastId) {
            me.getParentForm().refreshPickBillGrid();
        }
    },
    onWndShow: function () {
        var me = this;
        me.__saved = false;

        var el = me.getEl() || Ext.getBody();
        el.mask(ERP.Const.LOADING);
        Ext.Ajax.request({
            url : ERP.Const.BASE_URL + "Home/Sale/getAllSite",
            method : "POST",
            callback : function(options, success, response) {
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    //me.__storeSite.add(data.extData.site);
                    me.__storeArea.add(data.extData.area);
                } else {
                    ERP.MsgBox.showInfo("网络错误");
                }

                el.unmask();
            }
        });
    },
    __setSiteInfo:function(item){
        Ext.getCmp("siteid").setValue(item.id);
        Ext.getCmp("editAddressDetail").setValue(item.address);
    }
});