/**
 * Git 在线升级 - 前端客户端（框架无关）
 *
 * 依赖可注入：
 *  - request(options): Promise 或 callback 风格 AJAX
 *  - terminal: { addText, setProgress, setError }
 *  - confirm / alert / reload
 *
 * @example
 * const client = new GitOnlineUpdater({
 *   apiUrl: '/api/update.php',
 *   request: (opt) => LCMS.util.ajax({...}),
 *   terminal: tplxTerminal,
 * });
 * client.mount();
 */
(function (global) {
  'use strict';

  function defaults(options) {
    return Object.assign({
      apiUrl: '',
      // (opts) => void；opts: { type, url, data, timeout, loading, success, error, complete }
      request: null,
      terminal: null,
      // 系统数据就绪后
      onReady: null,
      // 更新完成后（在 reload 前），可返回 false 阻止默认 reload
      onComplete: null,
      // 同步数据结构钩子，可选
      syncUrl: '',
      confirm: function (msg, ok) {
        if (window.layer && layer.confirm) {
          layer.confirm(msg, { title: '提示', btn: ['确认更新', '取消'], area: '300px' }, function (i) {
            layer.close(i);
            ok && ok();
          });
        } else if (window.confirm(msg)) {
          ok && ok();
        }
      },
      alert: function (msg, yes) {
        if (window.top && top.layer && top.layer.alert) {
          top.layer.alert(msg, { closeBtn: 0, yes: yes });
        } else {
          window.alert(msg);
          yes && yes();
        }
      },
      reload: function () {
        (window.top || window).location.reload();
      },
      skipFiles: ['README.md', 'LICENSE.md', 'favicon.ico'],
      skipPrefixes: ['install/'],
      betaMaxLogs: 10,
    }, options || {});
  }

  function GitOnlineUpdater(options) {
    this.opt = defaults(options);
    this.sysdata = {};
    this.lastdata = { version: '获取中...', logs: [], files: {} };
    this.loading = true;
    this.noupdate = false;
  }

  GitOnlineUpdater.prototype.api = function (action, data, extra) {
    var self = this;
    var opt = Object.assign({
      type: 'POST',
      url: this.opt.apiUrl + (this.opt.apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'action=' + encodeURIComponent(action),
      data: data || {},
      timeout: 60000,
      loading: false,
    }, extra || {});

    return new Promise(function (resolve, reject) {
      if (!self.opt.request) {
        reject(new Error('请配置 request 适配器'));
        return;
      }
      self.opt.request(Object.assign({}, opt, {
        success: function (res) { resolve(res); },
        error: function (err) { reject(err || new Error('请求失败')); },
      }));
    });
  };

  GitOnlineUpdater.prototype.term = function (payload) {
    if (!this.opt.terminal || !this.opt.terminal.addText) return;
    this.opt.terminal.addText(payload);
  };

  GitOnlineUpdater.prototype.progress = function (n) {
    if (this.opt.terminal && this.opt.terminal.setProgress) {
      this.opt.terminal.setProgress(n);
    }
  };

  GitOnlineUpdater.prototype.error = function (msg) {
    if (this.opt.terminal && this.opt.terminal.setError) {
      this.opt.terminal.setError(msg);
    }
  };

  GitOnlineUpdater.prototype.addText = function (text, danger) {
    var info = '';
    try {
      info = new Date().toISOString().replace('T', ' ').slice(0, 19);
    } catch (e) {}
    this.term({ text: text, info: info, color: danger ? 'danger' : null });
  };

  GitOnlineUpdater.prototype.formatLogs = function (logs) {
    return (logs || []).map(function (item) {
      item.message = String(item.message || '')
        .replace(/(新增|重构|重写|更新|回滚) - /g, '<span class="update-git-tag">$1</span>')
        .replace(/修复 - /g, '<span class="update-git-tag update-git-tag-danger">修复</span>')
        .replace(/优化 - /g, '<span class="update-git-tag update-git-tag-success">优化</span>')
        .replace(/(移除|删除) - /g, '<span class="update-git-tag update-git-tag-warn">移除</span>')
        .replace(/\n/g, '<br/>');
      item.date = String(item.date || '').split(' ')[0];
      return item;
    });
  };

  GitOnlineUpdater.prototype.mount = function () {
    var self = this;
    this.term({ text: '获取当前框架信息', progress: true });
    return this.api('data', {}, { type: 'GET' }).then(function (res) {
      self.progress(100);
      self.sysdata = (res && res.data) || {};
      if (typeof self.opt.onReady === 'function') {
        self.opt.onReady(self.sysdata);
      }
      return new Promise(function (r) { setTimeout(r, 500); });
    }).then(function () {
      return self.checkVer();
    }).catch(function (e) {
      self.addText((e && e.message) || '初始化失败', true);
    });
  };

  GitOnlineUpdater.prototype.checkVer = function () {
    var self = this;
    this.term({ text: '获取最新框架版本', progress: true });
    return this.api('check', {}, { type: 'GET' }).then(function (res) {
      self.loading = false;
      self.progress(100);
      setTimeout(function () { self.addTips(); }, 500);

      if (!res) return;
      if (res.code === 0) {
        self.noupdate = res.msg;
        if (res.data && typeof res.data === 'object') {
          res.data.logs = self.formatLogs(res.data.logs || []);
          if (!res.data.version) {
            res.data.version = res.msg;
          }
          self.lastdata = res.data;
        } else {
          self.lastdata.version = res.msg;
          self.lastdata.logs = [];
        }
        self.addText(res.msg, true);
        return;
      }
      if (res.code === 1 && res.data) {
        res.data.logs = self.formatLogs(res.data.logs || []);
        self.lastdata = res.data;
        self.noupdate = false;
        self.addText('发现新版本：' + res.data.version);
        return;
      }
      self.addText(res.msg || '检查失败', true);
    });
  };

  GitOnlineUpdater.prototype.startUpdate = function () {
    var self = this;
    if (this.sysdata.ref === 'beta' && (this.lastdata.logs || []).length >= this.opt.betaMaxLogs) {
      this.addText('您已超过' + this.opt.betaMaxLogs + '个版本未更新，请使用手动更新！', true);
      return;
    }
    this.opt.confirm('更新前请先备份：① 数据库整库导出 ② 站点源码打包。确认已备份后再继续？', function () {
      self.checkPower();
    });
  };

  GitOnlineUpdater.prototype.checkPower = function () {
    var self = this;
    this.addText('开始检查目录权限');
    var ok = true;
    var dirs = Object.keys(this.sysdata.powerdir || {});

    var checkFile = function () {
      self.addText('开始检查文件权限');
      var powerfile = self.sysdata.powerfile;
      if (!powerfile) ok = false;
      setTimeout(function () {
        self.term({ text: '文件权限', info: powerfile ? '√ 权限正确' : '× 权限错误' });
        setTimeout(function () {
          if (ok) {
            self.term('　');
            self.addText('准备下载更新文件');
            self.eachFile('added', 0);
          } else {
            self.addText('更新失败，目录与关键文件必须可写', true);
          }
        }, 1000);
      }, 1000);
    };

    var checkDir = function (i) {
      var name = dirs[i];
      if (name === undefined) {
        checkFile();
        return;
      }
      var pass = self.sysdata.powerdir[name];
      self.term({ text: '/' + name, info: pass ? '√ 权限正确' : '× 权限错误' });
      if (!pass) ok = false;
      setTimeout(function () { checkDir(++i); }, 1000);
    };
    checkDir(0);
  };

  GitOnlineUpdater.prototype.shouldSkip = function (file) {
    if (this.opt.skipFiles.indexOf(file) !== -1) return true;
    for (var i = 0; i < this.opt.skipPrefixes.length; i++) {
      if (file.indexOf(this.opt.skipPrefixes[i]) === 0) return true;
    }
    return false;
  };

  GitOnlineUpdater.prototype.eachFile = function (type, index) {
    var self = this;
    var list = ((this.lastdata.files || {})[type] || []).slice();
    var total = list.length;
    if (index >= total) {
      if (type === 'added') return this.eachFile('modified', 0);
      if (type === 'modified') return this.eachFile('zip', 0);
      return total > 0 ? this.unZipFile() : this.copyFile();
    }
    var file = list[index];
    if (this.shouldSkip(file)) {
      setTimeout(function () { self.eachFile(type, ++index); }, 200);
      return;
    }
    if (type === 'zip') {
      this.downZip(type, total, index, file);
    } else {
      this.downFile(type, total, index, file);
    }
  };

  GitOnlineUpdater.prototype.downFile = function (type, total, index, file) {
    var self = this;
    this.term({ text: '[' + (index + 1) + '/' + total + '] ' + type + ':/' + file, progress: true });
    this.api('down', { file: file }).then(function (res) {
      if (res.code == 1) {
        self.progress(100);
        setTimeout(function () { self.eachFile(type, ++index); }, 400);
      } else {
        self.error(res.msg);
        self.addText('更新失败', true);
        self.reVersion();
      }
    }).catch(function (e) {
      self.addText((e && e.message) || '下载失败', true);
      self.reVersion();
    });
  };

  GitOnlineUpdater.prototype.downZip = function (type, total, index, file) {
    var self = this;
    this.term({ text: '获取文件信息', progress: true });
    this.api('down_info', { file: file }).then(function (res) {
      if (res.code != 1) {
        self.error(res.msg);
        self.addText('更新失败', true);
        return self.reVersion();
      }
      self.progress(100);
      setTimeout(function () {
        self.term({ text: '[' + (index + 1) + '/' + total + '] ' + file, progress: true });
        self.api('down_zip', { url: res.data.url }, { type: 'GET' });
        self.getZipSize(type, index, res.data.size);
      }, 500);
    });
  };

  GitOnlineUpdater.prototype.getZipSize = function (type, index, totalSize) {
    var self = this;
    totalSize = totalSize * 1 || 1;
    var tries = 0;
    var poll = function () {
      if (tries > 120) {
        self.error('下载超时');
        self.addText('更新失败', true);
        return self.reVersion();
      }
      tries++;
      self.api('down_status', {}, { type: 'GET' }).then(function (res) {
        if (res.code != 1) {
          self.error(res.msg);
          self.addText('更新失败', true);
          return self.reVersion();
        }
        var size = res.data * 1;
        if (size >= totalSize && size > 0) {
          self.progress(100);
          setTimeout(function () { self.eachFile(type, ++index); }, 500);
        } else {
          self.progress(Math.min(99, (size / totalSize) * 100));
          setTimeout(poll, 1000);
        }
      });
    };
    poll();
  };

  GitOnlineUpdater.prototype.copyFile = function () {
    var self = this;
    this.term({ text: '处理更新文件', progress: true });
    setTimeout(function () {
      self.api('copy', { commitish: self.lastdata.commitish }).then(function (res) {
        if (res.code == 1) {
          self.progress(100);
          setTimeout(function () { self.delFile(); }, 500);
        } else {
          self.error(res.msg);
          setTimeout(function () { self.reVersion(); }, 500);
        }
      });
    }, 1000);
  };

  GitOnlineUpdater.prototype.unZipFile = function () {
    var self = this;
    this.term({ text: '解压更新文件', progress: true });
    setTimeout(function () {
      self.api('copy_zip', { commitish: self.lastdata.commitish }).then(function (res) {
        if (res.code == 1) {
          self.progress(100);
          setTimeout(function () { self.delFile(); }, 500);
        } else {
          self.error(res.msg);
          setTimeout(function () { self.reVersion(); }, 500);
        }
      });
    }, 1000);
  };

  GitOnlineUpdater.prototype.delFile = function () {
    var self = this;
    this.term({ text: '清理陈旧文件', progress: true });
    var step = 1;
    var timer = setInterval(function () {
      self.progress(step * 10);
      if (step < 8) {
        step++;
        return;
      }
      clearInterval(timer);
      var files = ((self.lastdata.files || {}).removed || []).slice();
      self.api('remove', { files: files }).then(function () {
        self.progress(100);
        setTimeout(function () { self.syncData(); }, 500);
      });
    }, 400);
  };

  GitOnlineUpdater.prototype.syncData = function () {
    var self = this;
    this.term({ text: '同步数据结构（自动执行升级 SQL）', progress: true });
    var finish = function (ok, detail) {
      if (detail) {
        self.term({
          text: detail,
          color: ok === false ? 'danger' : (ok ? '' : 'warning'),
        });
      }
      self.progress(100);
      setTimeout(function () {
        self.addText(ok === false ? '文件已更新，但 SQL 同步失败' : '更新完成');
        var cont = true;
        if (typeof self.opt.onComplete === 'function') {
          cont = self.opt.onComplete(self, { sqlOk: ok !== false }) !== false;
        }
        if (cont) {
          var msg = ok === false
            ? '代码已更新，但升级 SQL 执行失败，请查看终端日志或手动执行 upgrade/sql。'
            : '已成功更新到最新版本！';
          self.opt.alert(msg, function () {
            self.opt.reload();
          });
        }
      }, 500);
    };
    if (this.opt.syncUrl) {
      this.opt.request({
        type: 'GET',
        url: this.opt.syncUrl,
        timeout: 120000,
        success: function (res) {
          var ok = res && (res.code == 1 || res.code === undefined);
          var data = (res && res.data) || {};
          var detail = (res && res.msg) ? String(res.msg) : '';
          if (data.ran && data.ran.length) {
            detail += '；已执行：' + data.ran.join(', ');
          } else if (ok && data.pending && !data.pending.length) {
            detail = detail || '无待执行 SQL';
          }
          if (!ok && data.errors && data.errors.length) {
            detail += '；' + (data.errors[0].file || '') + ' ' + (data.errors[0].error || '');
          }
          finish(ok !== false, detail);
        },
        error: function () {
          finish(false, '同步数据结构请求失败（可稍后点「执行升级 SQL」重试）');
        },
      });
    } else {
      finish(true, '');
    }
  };

  GitOnlineUpdater.prototype.reVersion = function () {
    var self = this;
    this.term({ text: '正在回退版本号', progress: true });
    this.api('re_version', { version: this.sysdata.version }).then(function (res) {
      if (res.code == 1) {
        self.progress(100);
        self.addText('版本号已回退');
      } else {
        self.error(res.msg);
      }
      self.addText('更新失败', true);
    });
  };

  GitOnlineUpdater.prototype.addTips = function () {
    var ui = (this.sysdata && this.sysdata.ui) || {};
    if (ui.manual_tip) {
      this.term('　');
      this.term({ text: ui.manual_tip, color: 'warning' });
      if (ui.manual_url) {
        this.term({
          text: '手动更新说明：<a href="' + ui.manual_url + '" target="_blank">' + ui.manual_url + '</a>',
          color: 'warning',
        });
      }
    }
    this.term('　');
  };

  global.GitOnlineUpdater = GitOnlineUpdater;
})(typeof window !== 'undefined' ? window : this);
