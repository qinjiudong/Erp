// 新增或编辑仓位
Ext.define("ERP.Position.PositionEditForm", {
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
            title: entity === null ? "新增仓位" : "编辑仓位",
            modal: true,
            onEsc: Ext.emptyFn,
            width: 400,
            height: 200,
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
                            id: "editParentOrg",
                            xtype: "ERP_position_editor",
                            parentItem: me,
                            fieldLabel: "上级仓位",
                            name:"fullname",
                            listeners: {
                                specialkey: {
                                    fn: me.onEditParentOrgSpecialKey,
                                    scope: me
                                }
                            }
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
                            id: "editPid",
                            xtype: "hidden",
                            name: "pid",
                            value: entity === null ? null : entity.get("pid")
                        },
                        {
                            id: "editWherehouseId",
                            xtype: "hidden",
                            name: "wherehouse_id",
                            value: entity === null ? null : entity.get("wherehouse_id")
                        },
                        {
                            id: "editCode",
                            fieldLabel: "编码",
                            allowBlank: false,
                            blankText: "没有输入编码",
                            beforeLabelTextTpl: ERP.Const.REQUIRED,
                            name: "code",
                            value: entity === null ? null : entity.get("Code"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
                                    scope: me
                                }
                            }
                        },
                        {
                            id: "editSort",
                            fieldLabel: "排序",
                            allowBlank: true,
                            name: "sort",
                            value: entity === null ? 10 : entity.get("Sort"),
                            listeners: {
                                specialkey: {
                                    fn: me.onEditCodeSpecialKey,
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
            url: me.getBaseURL() + "Home/Position/positioninfos",
            method: "POST",
            params: {id: entity.get("id")},
            callback: function (options, success, response) {
                form.getEl().unmask();
                if (success) {
                    var data = Ext.JSON.decode(response.responseText);
                    Ext.getCmp("editWherehouseId").setValue(data.wherehouse_id);
                    Ext.getCmp("editName").setValue(data.name);
                    Ext.getCmp("editCode").setValue(data.code);
                    Ext.getCmp("editPid").setValue(data.pid);
                    Ext.getCmp("editSort").setValue(data.sort);
                    Ext.getCmp("editParentOrg").setValue(data.pidname);
                }
            }
        });
    },
    setParentOrg: function (data) {
    		//if(data.pid == -1){alert('仓位选择错误');return;}
        Ext.getCmp("editParentOrg").setValue(data.text);
        Ext.getCmp("editWherehouseId").setValue(data.wherehouse_id);
        Ext.getCmp("editPid").setValue(data.pid);
    },
    // private
    onOK: function () {
        var me = this;
        var f = Ext.getCmp("editForm");
        var el = f.getEl();
        el.mask("数据保存中...");
        f.submit({
            url: me.getBaseURL() + "Home/Position/editPosition",
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
            Ext.getCmp("editCode").focus();
        }
    },
});