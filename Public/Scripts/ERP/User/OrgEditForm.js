// 新增或编辑组织机构
Ext.define("ERP.User.OrgEditForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    getBaseURL: function () {
        return ERP.Const.BASE_URL;
    },
    initComponent: function () {
        var me = this;
        var entity = me.getEntity();

        Ext.apply(me, {
            title: entity === null ? "新增组织机构" : "编辑组织机构",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 160,
            layout: "fit",
            defaultFocus: "editName",
            items: [
                {
                    id: "editForm",
                    xtype: "form",
                    layout: "form",
                    height: "100%",
                    bodyPadding: 5,
                    defaultType: 'textfield',
                    fieldDefaults: {
                        labelWidth: 50,
                        labelAlign: "right",
                        labelSeparator: "",
                        msgTarget: 'side'
                    },
                    items: [
                        {
                            xtype: "hidden",
                            name: "id",
                            value: entity === null ? null : entity.get("id")
                        },
                        {
                            id: "editName",
                            fieldLabel: "名称",
                            allowBlank: false,
                            blankText: "没有输入名称",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "name",
                            value: entity === null ? null : entity.get("text"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditNameSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editParentOrg",
                            xtype: "ERP_parent_org_editor",
                            parentItem: me,
                            fieldLabel: "上级组织",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditParentOrgSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editParentOrgId",
                            xtype: "hidden",
                            name: "parentId",
                            value: entity === null ? null : entity.get("parentId")
                        },
                        {
                            id: "editOrgCode",
                            fieldLabel: "编码",
                            allowBlank: false,
                            blankText: "没有输入编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "orgCode",
                            value: entity === null ? null : entity.get("orgCode"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditOrgCodeSpecialKey,
                                    scope: me
                                }
                            }
                        }
                    ],
                    buttons: [
                        {
                            text: "确定",
                            formBind: true,
                            iconCls: "ERP-button-ok",
                            handler: me.onOK,
                            scope: me
                        },
                        {
                            text: "取消", handler: function () {
                                ERP.MsgBox.confirm("请确认是否取消操作?", function () {
                                    me.close();
                                });
                            }, scope: me
                        }
                    ]
                }
            ],
            listeners: {
                show: {
                    fn: me.onEditFormShow,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },
    onEditFormShow: function () {
        var me = this;

        var entity = this.getEntity();
        if (entity === null) {
            return;
        }
        var form = this;
        form.getEl().mask("数据加载中...");
        Ext.Ajax.request({
            url: me.getBaseURL() + "Home/User/orgParentName",
            method: "POST",
            params: {id: entity.get("id")},
            callback: function (options, success, response) {
                form.getEl().unmask();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    Ext.getCmp("editParentOrg").setValue(data.parentOrgName);
                    Ext.getCmp("editParentOrgId").setValue(data.parentOrgId);
                    Ext.getCmp("editName").setValue(data.name);
                    Ext.getCmp("editOrgCode").setValue(data.orgCode);
                }
            }
        });
    },
    setParentOrg: function (data) {
        var editParentOrg = Ext.getCmp("editParentOrg");
        editParentOrg.setValue(data.fullName);
        var editParentOrgId = Ext.getCmp("editParentOrgId");
        editParentOrgId.setValue(data.id);
    },
    // private
    onOK: function () {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask("数据保存中...");
        f.submit({
            url: me.getBaseURL() + "Home/User/editOrg",
            method: "POST",
            success: function (form, action) {
                el.unmask();
                me.close();
                me.getParentForm().freshOrgGrid();
            },
            failure: function (form, action) {
                el.unmask();
                ERP.MsgBox.showInfo(action.result.msg, function () {
                    Ext.getCmp("editName").focus();
                });
            }
        });
    },
    onEditNameSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editParentOrg").focus();
        }
    },
    onEditParentOrgSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            Ext.getCmp("editOrgCode").focus();
        }
    },
    onEditOrgCodeSpecialKey: function (field, e) {
        if (e.getKey() == e.ENTER) {
            var f = Ext.getCmp("editForm");
            if (f.getForm().isValid()) {
                this.onOK();
            }
        }
    }
});