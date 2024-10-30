(function ($) {
    'use strict';
    $(document).ready(function () {
        let script_data = countera_local_script_data;

        toastr.options = {
            "closeButton": true,
            "positionClass": "toast-top-right"
        }

        let  exportAction = function (e, dt, node, config) {
            let url = script_data.export_url;
            let keywords = dt.ajax.params().search.value;

            if (keywords) {
                url += "&" + keywords;
            }

            location.href = url;
        }

        let table = $('#countera_table');
        var dt_table = table.DataTable({
            dom: '<"top">rlBt<"bottom"pi><"clear">',
            lengthChange: true,
            stateSave: true,
            buttons: [{
                    extend: 'csv',
                    text: script_data.text.export_csv,
                    className: 'btn btn-warning btn-sm',
                    action: exportAction
                }, ],
            processing: true,
            serverSide: true,
            colReorder: true,
            "scrollY": "300px",
            "scrollCollapse": true,
            ajax: {url: script_data.ajax_url, data: {action: "countera_get_user_post_views_count"}},
            columns: [
                {data: "id"},
                {data: "user_login", render: function (data, type, row, meta) {
                        return `<a href="${row.user_link}">${row.username}</a>`;
                    }},
                {data: "post_title", render: function (data, type, row, meta) {
                        return `<a href="${row.post_link}">${row.post_title}</a>`;
                    }},
                {data: "post_type"},
                {data: "view_count"},
                {data: "created_at"},
                {data: "modified_at"}
            ],
            language: {
                processing: script_data.text.datatable.processing,
                paginate: {
                    previous: script_data.text.datatable.previous,
                    next: script_data.text.datatable.next,
                },
                lengthMenu: script_data.text.datatable.lengthMenu,
                zeroRecords: script_data.text.datatable.zero_records,
                info: script_data.text.datatable.info,
                infoFiltered: script_data.text.datatable.info_filtered
            },
            columnDefs: [{
                    targets: 0,
                    orderable: false,
                    className: 'checkbox-column',
                    render: function (data, type, full, meta) {
                        return '<input type="checkbox" name="ids[]" value="' + $('<div/>').text(data).html() + '">';
                    }
                }],
            stateSaveParams: function (settings, data) {
                delete data.search;
                delete data.length;
                delete data.start;
                delete data.order;
            }
        });

        dt_table.buttons().container()
                .appendTo('#countera_table_wrapper .col-md-6:eq(0)');

        dt_table.on('draw', function () {
            table.find("th.checkbox-column").removeClass("sorting_asc");
        });

        let select_all = $('#select-all');

        select_all.on('click', function () {
            var rows = dt_table.rows({'search': 'applied'}).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        table.on('change', 'tbody input[type="checkbox"]', function () {
            if (!this.checked) {
                select_all.prop('checked', false);
            }
        });

        table.closest("form").on('submit', function (e) {
            e.preventDefault();

            let form = $(this);
            let  clicked_button = $(e.originalEvent.submitter);
            let loader = form.find("img");


            dt_table.$('input[type="checkbox"]').each(function () {
                if (!$.contains(document, this)) {
                    if (this.checked) {
                        form.append(
                                $('<input>')
                                .attr('type', 'hidden')
                                .attr('name', this.name)
                                .val(this.value)
                                );
                    }
                }
            });

            toastr.warning("<br /><button type='button' value='yes'>" + script_data.text.yes + "</button><button type='button'  value='no' >" + script_data.text.no + "</button>", script_data.text.are_you_sure,
                    {
                        allowHtml: true,
                        "positionClass": "toast-top-center",
                        onclick: function (toast) {
                            toastr.remove();
                            if (toast.target.value == 'yes') {
                                $.ajax({
                                    type: "post",
                                    url: script_data.ajax_url,
                                    data: form.serialize(),
                                    beforeSend: function () {
                                        clicked_button.hide();
                                        loader.show();
                                        console.log("Please wait...");
                                    },
                                    success: function (response) {
                                        toastr.remove();
                                        toastr.success(response.data.message || "Success");
                                        dt_table.draw();
                                    },
                                    error: function (xhr, status, error) {
                                        toastr.remove();
                                        if (xhr.status > 399 && xhr.status < 499) {
                                            toastr.error(xhr.responseJSON.data.message || "Error");
                                            return;
                                        }
                                        toastr.error(error);
                                    },
                                    complete: function () {
                                        clicked_button.show();
                                        loader.hide();
                                        console.log("complete");
                                    },
                                });
                            }
                        }
                    });
        });

        $("form#filter").on("submit", function (e) {
            e.preventDefault();
            let form = $(this);
            console.log(form.serializeArray());
            dt_table.search(form.serialize()).draw();
        });

        $("#show-hide-other-filter").click(function (e) {
            e.preventDefault();
            $("#other-filter").slideToggle();
        });
    });

})(jQuery);