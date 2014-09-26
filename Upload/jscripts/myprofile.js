/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Mohamed Benjelloun
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE. 
 */

var MyProfile = {
    /* this should be overloaded by another script, this should tell us what member UID are we visiting */
    memberUid: 0,
    /* ajax enabled ? a script will overload this variable by the setting in admin cp */
    ajax: false,
    /* we start at comment page 1 */
    commentsPage: 1,
    /* min length */
    commentsMinLength: 2,
    /* max length */
    commentsMaxLength: 5000,
    /* sceditor */
    commentsSCEditor: true,
    /* changed if any retrieval has been done */
    commentsRetrieved: false,
    /* use to prevent infinite page decrease */
    pageDecreased: false,
    /* called when page is ready */
    init: function() {
        /* report buttons are always hooked either way */
        $(document).on("click", "button.comments-report", MyProfile.commentsReport)
                .on("submit", "form#comments-edit-form", MyProfile.commentsEditSubmit)
				.on("click", "button.comments-delete-all", MyProfile.commentsDeleteAll)
                .on("click", "button.comments-edit", MyProfile.commentsEdit);
        if (!this.ajax) {
            return;
        }

        $(document).on("click", "button.comments-action", MyProfile.commentsAction)
                .on("click", "button.comments-delete", MyProfile.commentsDelete)
                .on("click", "button.comments-reply", MyProfile.commentsReply)
                .on("click", "button.comments-approve", MyProfile.commentsApprove)
                .on("submit", "form#comments-form", MyProfile.commentsSubmit);

        window.onhashchange = MyProfile.onHashChange;

        if (window.location.hash.length === 0) {
            window.location.hash = "#comments/" + MyProfile.commentsPage;
        }
        else {
            MyProfile.onHashChange();
        }
    },
    /* fired everytime the hash changes */
    onHashChange: function() {
        var parts = window.location.hash.split("/");
        if (!!parts[0] && parts[0] === "#comments" && !!parts[1] && parseInt(parts[1]) == parts[1]) {
            MyProfile.commentsRetrieve(parseInt(parts[1]));
        }
        else {
            if (!MyProfile.commentsRetrieved) {
                /* comments have not been retrieved so far */
                MyProfile.commentsRetrieve();
            }
        }
        /* call highlight comment */
        MyProfile.commentsHighlight();
    },
    /* retrieves a list of comments available on a specific page */
    commentsRetrieve: function(page) {
        if (typeof page === "undefined") {
            page = MyProfile.commentsPage;
        }
        MyProfile.commentsPage = page;
        $.ajax({
            "url": "xmlhttp.php?action=comments-retrieve",
            "type": "POST",
            "data": {
                "memberuid": MyProfile.memberUid,
                "my_post_key": my_post_key,
                "page": page
            },
            "dataType": "json",
            "success": function(obj) {
                var string = "";
                if (obj.comments.length === 0) {
                    string = obj.empty;
                    if (MyProfile.ajax && page > 1 && !MyProfile.pageDecreased) {
                        MyProfile.pageDecreased = true;
                        window.location.hash = "#comments/" + (page - 1);
                        return;
                    }
                    else if (MyProfile.ajax && page === 1) {
                        MyProfile.pageDecreased = true;
                    }
                }
                else {
                    for (var i = 0; i < obj.comments.length; i++) {
                        string += obj.comments[i];
                    }
                }
                $(".comments-content").html(string);
                $("#comments-total").html(obj.rows);
                if (typeof obj.pagination === "string") {
                    $(".comments-pagination>td").html(obj.pagination).show();
                }
                else {
                    $(".comments-pagination>td").hide();
                }
                /* check for any highlight */
                MyProfile.commentsHighlight();
                MyProfile.commentsRetrieved = true;
            }
        });
    },
    /* called when a comment is being approved */
    commentsApprove: function(event) {
        var cid = $(event.target).parent().parent().attr("data-cid");
        $.ajax({
            "url": "xmlhttp.php?action=comments-approve",
            "type": "POST",
            "dataType": "json",
            "data": {
                "cid": cid,
                "my_post_key": my_post_key
            },
            "success": function(obj) {
                if (!obj.error) {
                    MyProfile.commentsRetrieve();
                }
                else {
                    alert(obj.error_message);
                }
            }
        });
    },
    /* called on comment form submit */
    commentsSubmit: function(event) {
        event.preventDefault();
        var instance = {};
        if (MyProfile.commentsSCEditor) {
            instance = $("#message").sceditor("instance");
        }
        else {
            instance = $("#message");
        }
        if (instance.val().length < MyProfile.commentsMinLength || instance.val().length > MyProfile.commentsMaxLength) {
            alert(MyProfile.sprintf(lang.mp_comments_comment_wrong_length, MyProfile.commentsMinLength, MyProfile.commentsMaxLength));
            return;
        }
        var isprivate = $("select.select-comments-add[name='isprivate']").length === 1 ? $("select.select-comments-add[name='isprivate']").val() : 0;
        $.ajax({
            "url": "xmlhttp.php?action=comments-add",
            "type": "POST",
            "data": {
                "memberuid": MyProfile.memberUid,
                "my_post_key": my_post_key,
                "message": instance.val(),
                "isprivate": isprivate
            },
            "dataType": "json",
            "success": function(obj) {
                if (! obj.error) {
					instance.val("");
                    MyProfile.commentsRetrieve(1);
                }
                else {
                    alert(obj.error_message);
                }
            }
        });
    },
    /* called on every comment related action */
    commentsAction: function(event) {
        event.preventDefault();
    },
    /* called when replying to a comment */
    commentsReply: function(event) {

    },
    /* called when deleting a comment */
    commentsDelete: function(event) {
        if (!confirm(lang.mp_comments_confirm_delete)) {
            return;
        }
        var cid = $(event.target).parent().parent().attr("data-cid");
        $.ajax({
            "url": "xmlhttp.php?action=comments-delete",
            "type": "POST",
            "dataType": "json",
            "data": {
                "cid": cid,
                "my_post_key": my_post_key
            },
            "success": function(obj) {
                if (!obj.error) {
                    MyProfile.commentsRetrieve();
                }
                else {
                    alert(obj.error_message);
                }
            }
        });
    },
    /* called when a moderator is deleting all comments on a profile */
    commentsDeleteAll: function(event) {
        if (!confirm(lang.mp_comments_confirm_delete_all)) {
            return;
        }
        $.ajax({
            "url": "xmlhttp.php?action=comments-delete-all",
            "type": "POST",
            "dataType": "json",
            "data": {
                "memberuid": MyProfile.memberUid,
                "my_post_key": my_post_key
            },
            "success": function(obj) {
                if (!obj.error) {
					if(MyProfile.ajax) {
						MyProfile.commentsRetrieve(1);
					}
					else {
						window.location.href = rootpath + "/misc.php?action=comments-delete-all&memberuid=" + MyProfile.memberUid + "&my_post_key=" + my_post_key;
					}
                }
				else {
					alert(obj.error_message);
				}
            }
        });
    },
    /* called on comment edit */
    commentsEdit: function(event) {
        var cid = $(event.target).parent().attr("data-cid");
        // "/misc.php?action=comments-edit&cid=" + cid + "&my_post_key=" + my_post_key
        $.get(rootpath + "/misc.php?action=comments-edit&cid=" + cid + "&memberuid=" + MyProfile.memberUid + "&my_post_key=" + my_post_key, function(html) {
            $(html).appendTo('body').modal({fadeDuration: 250, zIndex: 5})
                    .on($.modal.OPEN, function(event, modal) {
                        modal.elm.css({
                            "top": "40%",
                            "left": "40%",
                            "width": "700px"
                        });
                        modal.elm.find("iframe").length === 1 && modal.elm.find("iframe").css({
                            "height": "120px"
                        });
                    });
        }).fail(function(obj) {
            obj.responseText && alert(obj.responseText);
        });
    },
    commentsEditSubmit: function(event) {
        event.preventDefault();
        if (MyProfile.commentsSCEditor) {
            instance = $("#message_edit").sceditor("instance");
        }
        else {
            instance = $("#message_edit");
        }
        if (instance.val().length < MyProfile.commentsMinLength || instance.val().length > MyProfile.commentsMaxLength) {
            alert(MyProfile.sprintf(lang.mp_comments_comment_wrong_length, MyProfile.commentsMinLength, MyProfile.commentsMaxLength));
            return;
        }
        var cid = $("input.comments-edit-submit").attr("data-cid");
        var isprivate = $("select.select-comments-edit[name='isprivate']").length === 1 ? $("select.select-comments-edit[name='isprivate']").val() : 0;
        $.ajax({
            "url": "xmlhttp.php?action=comments-do-edit",
            "type": "POST",
            "data": {
                "memberuid": MyProfile.memberUid,
                "my_post_key": my_post_key,
                "message": instance.val(),
                "isprivate": isprivate,
                "cid": cid
            },
            "dataType": "json",
            "success": function(obj) {
                if (!obj.error) {
                    if (MyProfile.ajax) {
                        MyProfile.commentsRetrieve();
                        instance.val("");
                        MyProfile.closeCurrentModal();
                    }
                    else {
                        window.location.href = rootpath + "/misc.php?action=comments-do-edit&page=" + MyProfile.commentsPage + "&memberuid=" + MyProfile.memberUid + "&my_post_key=" + my_post_key;
                    }
                }
                else {
                    alert(obj.error_message);
                    MyProfile.closeCurrentModal();
                }
            }
        });

    },
    commentsReport: function(event) {
        var cid = $(event.target).parent().attr("data-cid");
        MyBB.popupWindow("/report.php?type=comment&pid=" + cid);
    },
    sprintf: function() {
        if (arguments.length === 0) {
            return "";
        }
        else if (arguments.length === 1) {
            return arguments[0];
        }
        else {
            var string = arguments[0];
            for (var i = 1; i < arguments.length; i++) {
                string = string.replace("{" + i + "}", arguments[i]);
            }
            return string;
        }
    },
    closeCurrentModal: function() {
        $("a.close-modal").length > 0 && $("a.close-modal").click();
    },
    commentsHighlight: function() {
        var parts = window.location.hash.split("/");
        /* not very useful, but remove any previously highlited comments */
        $("td.trow_selected").length >= 1 && $("td.trow_selected").removeClass("trow_selected");
        /* search for comments to highlight */
        if (!!parts[2] && parts[2] === "highlight" && !!parts[3] && parseInt(parts[3]) == parts[3]) {
            if ($("td[data-cid='" + parseInt(parts[3]) + "']").length >= 1) {
                $("td[data-cid='" + parseInt(parts[3]) + "']").addClass("trow_selected");
            }
        }
    },
	buddylistLoadPage: function(page) {
		$.ajax({
			"url" : "xmlhttp.php?action=buddylist-load-page",
			"type" : "GET",
			"data" : {
				"my_post_key" : my_post_key,
				"page" : page,
				"memberuid" : MyProfile.memberUid
			},
			"dataType" : "json",
			"success": function(obj) {
				if(! obj.error) {
					if(obj.shown == 0 && obj.count > 0) {
						MyProfile.buddylistLoadPage(1);
					}
					else {
						$(".buddylist-content").replaceWith(obj.html);
					}
				}
			}
		});
	}
};

$(document).ready(function() {
    MyProfile.init();
});