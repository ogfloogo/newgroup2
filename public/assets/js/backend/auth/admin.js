define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auth/admin/index',
                    add_url: 'auth/admin/add',
                    edit_url: 'auth/admin/edit',
                    del_url: 'auth/admin/del',
                    multi_url: 'auth/admin/multi',
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function () {
                    if (parseInt($("td:eq(1)", this).text()) == Config.admin.id) {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        { field: 'state', checkbox: true, },
                        { field: 'id', title: 'ID' },
                        { field: 'username', title: __('Username') },
                        { field: 'agent_code', title: __('代理code') },
                        { field: 'nickname', title: __('Nickname') },
                        { field: 'groups_text', title: __('Group'), operate: false, formatter: Table.api.formatter.label },
                        { field: 'email', title: __('Email') },
                        { field: 'mobile', title: __('Mobile') },
                        { field: 'status', title: __("Status"), searchList: { "normal": __('Normal'), "hidden": __('Hidden') }, formatter: Table.api.formatter.status },
                        { field: 'auto_assign', title: __("是否自动分配新用户"), searchList: { "0": __('否'), "1": __('是') }, formatter: Table.api.formatter.status },

                        { field: 'logintime', title: __('Login time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                if (row.id == Config.admin.id) {
                                    return '';
                                }
                                if (row.agent_id) {
                                    $(table).data('operate-del', null);
                                } else {
                                    $(table).data('operate-del', true);
                                }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        agentindex: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auth/admin/agentindex',
                    add_url: 'auth/admin/addagent',
                    edit_url: 'auth/admin/editagent',
                    // del_url: 'auth/admin/del',
                    multi_url: 'auth/admin/multi',
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function () {
                    if (parseInt($("td:eq(1)", this).text()) == Config.admin.id) {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        { field: 'state', checkbox: true, },
                        { field: 'id', title: 'ID' },
                        // { field: 'username', title: __('Username') },
                        // { field: 'agent_code', title: __('代理code') },
                        { field: 'nickname', title: __('Nickname') },
                        { field: 'mobile', title: __('Mobile') },
                        { field: 'logintime', title: __('Login time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true },
                        { field: 'invite_code', title: __('邀请码') },
                        { field: 'invite_url', title: __('邀请链接') },
                        //
                        // { field: 'type', title: __("type"), searchList: { "1": __('type 1'), "2": __('type 2') }, formatter: Table.api.formatter.status },
                        // { field: 'is_gift', title: __("is_gift"), searchList: { "0": __('is_gift 0'), "1": __('is_gift 1') }, formatter: Table.api.formatter.status },
                        // { field: 'is_return', title: __("is_return"), searchList: { "0": __('is_return 0'), "1": __('is_return 1') }, formatter: Table.api.formatter.status },
                        // { field: 'is_commission', title: __("is_commission"), searchList: { "0": __('is_commission 0'), "1": __('is_commission 1') }, formatter: Table.api.formatter.status },
                        // { field: 'is_bank', title: __("is_bank"), searchList: { "0": __('is_bank 0'), "1": __('is_bank 1') }, formatter: Table.api.formatter.status },
                        //
                        //
                        // // { field: 'groups_text', title: __('Group'), operate: false, formatter: Table.api.formatter.label },
                        // // { field: 'email', title: __('Email') },
                        // { field: 'status', title: __("Status"), searchList: { "normal": __('Normal'), "hidden": __('Hidden') }, formatter: Table.api.formatter.status },
                        // { field: 'auto_assign', title: __("是否自动分配新用户"), searchList: { "0": __('否'), "1": __('是') }, formatter: Table.api.formatter.status },

                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                // {
                                //     name: 'kefu',
                                //     text: __('配置客服'),
                                //     title: __('配置客服'),
                                //     classname: 'btn btn-xs btn-success btn-magic btn-dialog',
                                //     url: 'auth/admin/kefu',
                                //     callback: function (data) {
                                //         Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         return true;
                                //     }
                                // },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        addagent: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        editagent: function () {
            Form.api.bindevent($("form[role=form]"));
        },
    };
    return Controller;
});
