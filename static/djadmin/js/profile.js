/**
 * profile.js
 * by liangya
 * date: 2017-08-16
 */
$(function () {
  var $profileAvatar = $('#profile-avatar'),
    $profilePhone = $('#profile-phone'),
    $profileNick = $('#profile-nick'),
    $profileSex = $('#profile-sex'),
    $editNick = $('#edit-nick'),
    $editPassword = $('#edit-password'),
    $editSex = $('#edit-sex'),
    $nickModal = $('#nickModal'),
    $passwordModal = $('#passwordModal'),
    $sexModal = $('#sexModal'),
    $confirmNick = $('#confirm-nick'),
    $confirmPassword = $('#confirm-password'),
    $confirmSex = $('#confirm-sex');

  getUserInfo();

  // 获取用户信息
  function getUserInfo() {
    $.getJSON(__BASEURL__ + 'user_api/info', function (data) {
      if (data.success) {
        fillUserInfo(data.data);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 填充用户信息
  function fillUserInfo(user) {
    if (user.img) {
      $profileAvatar.attr('src', __UPLOADURL__ + user.img);
    }

    $profilePhone.text(user.mobile);

    $profileNick.text(user.username);
    $('#nick').val(user.username);

    if (user.sex) {
      $profileSex.text(user.sex);
      $('[name="sex"][value="' + user.sex + '"]').prop('checked', true);
    } else {
      $profileSex.text('保密');
      $('[name="sex"][value="保密"]').prop('checked', true);
    }
  }

  // 上传头像
  uploadFile('main_header', {
    browse_button: 'upload-avatar',
    container: 'upload-avatar-container',
    drop_element: 'upload-avatar-container',
    max_file_size: '10mb',
    chunk_size: '4mb',
    init: {
      'FileUploaded': function (up, file, info) {
        var res = JSON.parse(info.response);
        var halfpath = res.key;

        editAvatar(halfpath);
      }
    }
  });

  // 修改头像
  function editAvatar(img) {
    if (!img) {
      return false;
    }

    $.post(__BASEURL__ + 'user_api/edit', autoCsrf({
      field: 'img',
      value: img
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '修改成功'
        });
        $profileAvatar.attr('src', __UPLOADURL__ + img);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 修改昵称
  $editNick.on('click', function () {
    $("#nick-form").data('bootstrapValidator').destroy();
    $('#nick-form').data('bootstrapValidator', null);
    validatorNickForm();

    $nickModal.modal('show');
  });

  // 修改密码
  $editPassword.on('click', function () {
    $("#password-form").data('bootstrapValidator').destroy();
    $('#password-form').data('bootstrapValidator', null);
    validatorPasswordForm();

    $('#old_password').val('');
    $('#new_password').val('');
    $('#re_password').val('');

    $passwordModal.modal('show');
  });

  // 修改性别
  $editSex.on('click', function () {
    $("#sex-form").data('bootstrapValidator').destroy();
    $('#sex-form').data('bootstrapValidator', null);
    validatorSexForm();

    $sexModal.modal('show');
  });

  validatorNickForm();
  validatorPasswordForm();
  validatorSexForm();

  // 验证昵称表单
  function validatorNickForm() {
    $('#nick-form')
      .bootstrapValidator({
        fields: {
          nick: {
            validators: {
              notEmpty: {
                message: '昵称不能为空'
              },
              stringLength: {
                max: 10,
                message: '昵称不能超过10个字符'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var nick = $('#nick').val();

        $confirmNick.prop('disabled', true);

        $.post(__BASEURL__ + 'user_api/edit', autoCsrf({
          field: 'username',
          value: nick
        }), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: '修改成功'
            });
            $nickModal.modal('hide');
            $profileNick.text(nick);
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $confirmNick.prop('disabled', false);
        });
      });
  }

  // 验证密码表单
  function validatorPasswordForm() {
    $('#password-form')
      .bootstrapValidator({
        fields: {
          old_password: {
            validators: {
              notEmpty: {
                message: '旧密码不能为空'
              },
              stringLength: {
                min: 6,
                max: 25,
                message: '密码格式不正确'
              }
            }
          },
          new_password: {
            validators: {
              notEmpty: {
                message: '新密码不能为空'
              },
              regexp: {
                regexp: PregRule.Pwd,
                message: '请使用字母、数字和符号的密码组合，8-25个字符'
              }
            }
          },
          re_password: {
            validators: {
              notEmpty: {
                message: '确认密码不能为空'
              },
              identical: {
                field: 'new_password',
                message: '两次输入密码不一致'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var old_password = $('#old_password').val(),
          new_password = $('#new_password').val(),
          re_password = $('#re_password').val();

        $confirmPassword.prop('disabled', true);

        $.post(__BASEURL__ + 'user_api/update_password', autoCsrf({
          old_password: old_password,
          new_password: new_password,
          re_password: re_password,
        }), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: '修改成功'
            });
            $passwordModal.modal('hide');
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $confirmPassword.prop('disabled', false);
        });
      });
  }

  // 验证性别表单
  function validatorSexForm() {
    $('#sex-form')
      .bootstrapValidator({
        fields: {
          sex: {
            validators: {
              notEmpty: {
                message: '性别不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var sex = $('[name="sex"]:checked').val();

        $confirmSex.prop('disabled', true);

        $.post(__BASEURL__ + 'user_api/edit', autoCsrf({
          field: 'sex',
          value: sex
        }), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: '修改成功'
            });
            $profileSex.text(sex);
            $sexModal.modal('hide');
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
          $confirmSex.prop('disabled', false);
        });
      });
  }
});