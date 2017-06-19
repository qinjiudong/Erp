// 客户分类 - 新增或编辑界面
Ext.define("ERP.InvTransfer.UplodeForm", {
    extend: "Ext.window.Window",
    config: {
        parentForm: null,
        entity: null
    },
    initComponent: function () {
        var me = this;

        var upfiled = Ext.create('Ext.Window',{
                    title:'上传文件',
                    width:400,
                    height:300,
                    layout:'fit',
                    id:'upField',
                    collapsible : true,
                    maximizable : true,
                    plain : true,
                    buttonAlign : 'center',
                    html : '' +
                            '<input name="file" type="file" value="" id="file" />' +
                            '<input type="hidden" value=\'{"table":"goodsOrder","path":"ss"}\' name="json" id="json">' +
                            '<input type="hidden" value="upload" name="action">' +
                            '',
                    draggable : true,
                    bbar:[{
                        text:'提交',
                        handler:me.onUplode
                    }]
                })
                        upfiled.show();
        },
        onUplode: function() {
            var me = this;
            me.__canEditGoodsPrice = false;
            var el = me.getEl() || Ext.getBody();
            el.mask(ERP.Const.LOADING);
            
            var fileObj = document.getElementById("file").files[0]; // 获取文件对象
            var FileController = ERP.Const.BASE_URL + "Home/InvTransfer/uplode";                    // 接收上传文件的后台地址 

            // FormData 对象
            var form = new FormData();
//            form.append("author", "hooyes");                        // 可以增加表单数据
            form.append("file", fileObj);                           // 文件对象
            // XMLHttpRequest 对象
            var xhr = new XMLHttpRequest();
            xhr.open("post", FileController, true);
            el.unmask();
            xhr.onload = function (e) {
                if(this.status == 200||this.status == 304){
//                    console.log(this.responseText);
                    Ext.getCmp('upField').hide();
                }
                var    datas = Ext.JSON.decode(this.responseText);
console.log(datas);
                Ext.Msg.alert('Success', '导入成功');
                
            var excelForm = Ext.getCmp('inList');
                for(var i = 0; i < datas.count; i++) { 
                    excelForm.store.add({
                        goodsCode:datas.list[i][0],
                        goodsName:datas.list[i][1],
                        goodsSpec:datas.list[i][2],
                        goodsCount:datas.list[i][3],
                        unitName:datas.list[i][4]
                    })
                }
            };
            xhr.send(form);
        },
   
});
