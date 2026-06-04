<div class="titlePage">
  <h2>{'Bulk Sync Manager'|@translate}</h2>
</div>

<style>
  .bsm-shell {
    background: linear-gradient(180deg, #f7f9fc 0%, #ffffff 100%);
    border: 1px solid #d9e2ef;
    border-radius: 20px;
    box-shadow: 0 16px 34px rgba(17, 24, 39, 0.08);
    overflow: hidden;
  }

  .bsm-hero {
    background: linear-gradient(135deg, #153658 0%, #245d92 55%, #2f8e87 100%);
    color: #fff;
    padding: 26px 28px;
  }

  .bsm-hero h3,
  .bsm-hero p {
    color: inherit;
    margin: 0;
  }

  .bsm-hero h3 {
    font-size: 28px;
    margin-bottom: 8px;
  }

  .bsm-hero p {
    max-width: 70ch;
    opacity: 0.92;
  }

  .bsm-body {
    display: grid;
    gap: 22px;
    grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
    padding: 26px 28px 30px;
  }

  .bsm-card {
    background: #fff;
    border: 1px solid #e4ebf4;
    border-radius: 18px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    overflow: hidden;
  }

  .bsm-card-head {
    border-bottom: 1px solid #e7edf5;
    padding: 18px 20px 14px;
  }

  .bsm-card-head h4,
  .bsm-card-head p {
    margin: 0;
  }

  .bsm-card-head h4 {
    color: #1d2a3a;
    font-size: 20px;
  }

  .bsm-card-head p {
    color: #657286;
    margin-top: 6px;
  }

  .bsm-card-body {
    padding: 18px 20px 22px;
  }

  .bsm-form-grid {
    display: grid;
    gap: 16px;
  }

  .bsm-form-grid label {
    color: #526172;
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin-bottom: 8px;
    text-transform: uppercase;
  }

  .bsm-form-grid select {
    border: 1px solid #cfd9e6;
    border-radius: 12px;
    box-sizing: border-box;
    font-size: 14px;
    padding: 12px 14px;
    width: 100%;
  }

  .bsm-field-help {
    color: #6a788a;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 8px;
  }

  .bsm-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
  }

  .bsm-btn {
    background: linear-gradient(135deg, #1e6bb8 0%, #295896 100%);
    border: 0;
    border-radius: 12px;
    color: #fff;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    padding: 11px 15px;
  }

  .bsm-btn-secondary {
    background: #fff;
    border: 1px solid #d0dae7;
    border-radius: 12px;
    color: #203247;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    padding: 11px 15px;
  }

  .bsm-status-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .bsm-stat {
    background: #f7fafc;
    border: 1px solid #e3ebf5;
    border-radius: 14px;
    padding: 14px;
  }

  .bsm-stat strong,
  .bsm-stat span {
    display: block;
  }

  .bsm-stat strong {
    color: #19324d;
    font-size: 24px;
    margin-bottom: 6px;
  }

  .bsm-stat span {
    color: #627284;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .bsm-current {
    background: linear-gradient(135deg, #eff6ff 0%, #f6fffb 100%);
    border: 1px solid #d7e6eb;
    border-radius: 16px;
    margin-top: 16px;
    padding: 16px 18px;
  }

  .bsm-current strong,
  .bsm-current p {
    margin: 0;
  }

  .bsm-current strong {
    color: #18314e;
    display: block;
    margin-bottom: 6px;
  }

  .bsm-current p {
    color: #607083;
    line-height: 1.55;
  }

  .bsm-progress {
    margin-top: 16px;
  }

  .bsm-progress-bar {
    background: #e6edf7;
    border-radius: 999px;
    height: 12px;
    overflow: hidden;
    width: 100%;
  }

  .bsm-progress-fill {
    background: linear-gradient(135deg, #1e6bb8 0%, #2e8d87 100%);
    height: 100%;
    transition: width 180ms ease;
    width: 0%;
  }

  .bsm-progress-meta {
    color: #607083;
    font-size: 13px;
    margin-top: 8px;
  }

  .bsm-live-list {
    list-style: none;
    margin: 16px 0 0;
    padding: 0;
  }

  .bsm-live-list li {
    align-items: flex-start;
    border-top: 1px solid #e8edf4;
    color: #425063;
    display: grid;
    gap: 10px;
    grid-template-columns: 86px minmax(0, 1fr);
    padding: 12px 0;
  }

  .bsm-live-list li:first-child {
    border-top: 0;
    padding-top: 0;
  }

  .bsm-live-time {
    color: #7a8798;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .bsm-live-message {
    line-height: 1.5;
  }

  .bsm-note {
    color: #607083;
    line-height: 1.6;
    margin: 0;
  }

  .bsm-alert {
    background: #fff4e8;
    border: 1px solid #f0c48a;
    border-radius: 14px;
    color: #7a4a11;
    display: none;
    margin-top: 16px;
    padding: 13px 15px;
  }

  .bsm-alert.is-visible {
    display: block;
  }

  @media (max-width: 920px) {
    .bsm-body {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="bsm-shell">
  <div class="bsm-hero">
    <h3>{'Bulk Sync Manager'|@translate}</h3>
    <p>{'Run large synchronizations in short native Piwigo batches so the browser request does not time out.'|@translate}</p>
  </div>

  <div class="bsm-body">
    <div class="bsm-card">
      <div class="bsm-card-head">
        <h4>{'Start a bulk synchronization'|@translate}</h4>
        <p>{'Choose a local site and optionally a root album. The plugin scans directories in short batches first and then synchronizes one album at a time.'|@translate}</p>
      </div>
      <div class="bsm-card-body">
        <div class="bsm-form-grid">
          <div>
            <label for="bsm-site">{'Local site'|@translate}</label>
            <select id="bsm-site">
              {foreach from=$bsm_sites item=site}
                <option value="{$site.id}"{if $site.id eq $bsm_selected_site_id} selected{/if}>{$site.label}</option>
              {/foreach}
            </select>
          </div>

          <div>
            <label for="bsm-root-category">{'Root album'|@translate}</label>
            <select id="bsm-root-category">
              <option value="0"{if $bsm_selected_root_category_id|default:0 eq 0} selected{/if}>{'Whole local site'|@translate}</option>
              {foreach from=$bsm_root_categories item=category}
                <option value="{$category.id}"{if $category.id eq $bsm_selected_root_category_id|default:0} selected{/if}>{$category.full_name}</option>
              {/foreach}
            </select>
          </div>

          <div>
            <label for="bsm-metadata-mode">{'Metadata mode'|@translate}</label>
            <select id="bsm-metadata-mode">
              <option value="new_only"{if $bsm_selected_metadata_mode|default:'new_only' eq 'new_only'} selected{/if}>{'Only read metadata for new files'|@translate}</option>
              <option value="all"{if $bsm_selected_metadata_mode|default:'new_only' eq 'all'} selected{/if}>{'Re-read metadata for all files'|@translate}</option>
            </select>
            <div class="bsm-field-help">{'Choose whether metadata should only be read for new files or refreshed for every file in each album.'|@translate}</div>
          </div>
        </div>

        <div class="bsm-actions">
          <button type="button" class="bsm-btn" id="bsm-start">{'Start bulk sync'|@translate}</button>
          <button type="button" class="bsm-btn-secondary" id="bsm-cancel">{'Cancel current job'|@translate}</button>
        </div>

        <div class="bsm-alert" id="bsm-alert"></div>
      </div>
    </div>

    <div class="bsm-card">
      <div class="bsm-card-head">
        <h4>{'Job status'|@translate}</h4>
        <p>{'The browser keeps sending short synchronization requests until the queue is finished.'|@translate}</p>
      </div>
      <div class="bsm-card-body">
        <div class="bsm-status-grid">
          <div class="bsm-stat">
            <strong id="bsm-status">{$bsm_active_job.status|default:'idle'}</strong>
            <span>{'Status'|@translate}</span>
          </div>
          <div class="bsm-stat">
            <strong id="bsm-phase">{$bsm_active_job.phase|default:'-'}</strong>
            <span>{'Phase'|@translate}</span>
          </div>
          <div class="bsm-stat">
            <strong id="bsm-processed">{if isset($bsm_active_job) and $bsm_active_job.phase eq 'directory_scan'}{$bsm_active_job.scanned_directories|default:0}{else}{$bsm_active_job.processed_categories|default:0}{/if}</strong>
            <span id="bsm-processed-label">{if isset($bsm_active_job) and $bsm_active_job.phase eq 'directory_scan'}{'Directories scanned'|@translate}{else}{'Albums processed'|@translate}{/if}</span>
          </div>
          <div class="bsm-stat">
            <strong id="bsm-total">{if isset($bsm_active_job) and $bsm_active_job.phase eq 'directory_scan'}{$bsm_active_job.remaining_directories|default:0}{else}{$bsm_active_job.total_categories|default:0}{/if}</strong>
            <span id="bsm-total-label">{if isset($bsm_active_job) and $bsm_active_job.phase eq 'directory_scan'}{'Directories remaining'|@translate}{else}{'Albums queued'|@translate}{/if}</span>
          </div>
        </div>

        <div class="bsm-current">
          <strong id="bsm-current-label">{'Current work item'|@translate}</strong>
          <p id="bsm-current-message">{$bsm_active_job.current_message|default:('No active job'|@translate)}</p>
        </div>

        <p class="bsm-note" style="margin-top:16px;" id="bsm-metadata-mode-note">
          {if isset($bsm_active_job) and $bsm_active_job.metadata_mode eq 'all'}
            {'Metadata mode currently re-reads metadata for all files.'|@translate}
          {else}
            {'Metadata mode currently only reads metadata for new files.'|@translate}
          {/if}
        </p>

        <div class="bsm-progress">
          <div class="bsm-progress-bar">
            <div class="bsm-progress-fill" id="bsm-progress-fill"></div>
          </div>
          <div class="bsm-progress-meta" id="bsm-progress-meta">
            {if isset($bsm_active_job)}
              {if $bsm_active_job.phase eq 'directory_scan'}
                {$bsm_active_job.scanned_directories|default:0} {'directories scanned'|@translate} . {$bsm_active_job.remaining_directories|default:0} {'directories remaining'|@translate} . {$bsm_active_job.created_categories|default:0} {'albums created'|@translate}
              {elseif isset($bsm_active_job.total_categories) and $bsm_active_job.total_categories gt 0}
                {$bsm_active_job.processed_categories|default:0} / {$bsm_active_job.total_categories|default:0} {'albums completed'|@translate}
              {else}
                {'Waiting for first batch'|@translate}
              {/if}
            {else}
              {'No active job'|@translate}
            {/if}
          </div>
        </div>

        <div class="bsm-current" style="margin-top:16px;">
          <strong>{'Live activity'|@translate}</strong>
          <ul class="bsm-live-list" id="bsm-live-list">
            {if isset($bsm_active_job) and !empty($bsm_active_job.recent_log_entries)}
              {foreach from=$bsm_active_job.recent_log_entries item=entry}
                <li>
                  <span class="bsm-live-time">{$entry.time|escape}</span>
                  <span class="bsm-live-message">{$entry.message|escape}</span>
                </li>
              {/foreach}
            {else}
            <li>
              <span class="bsm-live-time">--:--</span>
              <span class="bsm-live-message">{'No live activity yet'|@translate}</span>
            </li>
            {/if}
          </ul>
        </div>

        <p class="bsm-note" style="margin-top:16px;">
          {'This first version focuses on local sites and processes existing or newly discovered albums one after another.'|@translate}
        </p>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var adminUrl = '{$BSM_ADMIN|escape:'javascript'}';
    var token = '{$BSM_TOKEN|escape:'javascript'}';
    var activeJob = {if isset($bsm_active_job)}{$bsm_active_job.id|intval}{else}0{/if};
    var progressFill = document.getElementById('bsm-progress-fill');
    var progressMeta = document.getElementById('bsm-progress-meta');
    var liveList = document.getElementById('bsm-live-list');
    var alertBox = document.getElementById('bsm-alert');
    var isProcessing = false;

    function setText(id, value) {
      document.getElementById(id).textContent = value;
    }

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function showAlert(message) {
      if (!message) {
        alertBox.textContent = '';
        alertBox.classList.remove('is-visible');
        return;
      }

      alertBox.textContent = message;
      alertBox.classList.add('is-visible');
    }

    function renderLiveEntries(entries) {
      if (!entries || !entries.length) {
        liveList.innerHTML = '<li><span class="bsm-live-time">--:--</span><span class="bsm-live-message">{'No live activity yet'|@translate|escape:'javascript'}</span></li>';
        return;
      }

      liveList.innerHTML = entries.map(function(entry) {
        return '<li><span class="bsm-live-time">' + escapeHtml(entry.time) + '</span><span class="bsm-live-message">' + escapeHtml(entry.message) + '</span></li>';
      }).join('');
    }

    function updateProgress(job) {
      if (!job) {
        progressFill.style.width = '0%';
        progressMeta.textContent = '{'No active job'|@translate|escape:'javascript'}';
        return;
      }

      var total = Number(job.total_categories || 0);
      var processed = Number(job.processed_categories || 0);
      var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;

      if (job.phase === 'directory_scan') {
        var scanned = Number(job.scanned_directories || 0);
        var remaining = Number(job.remaining_directories || 0);
        var discovered = scanned + remaining;
        percent = discovered > 0 ? Math.min(100, Math.round((scanned / discovered) * 100)) : 0;
        progressMeta.textContent = scanned + ' {'directories scanned'|@translate|escape:'javascript'} . ' + remaining + ' {'directories remaining'|@translate|escape:'javascript'} . ' + Number(job.created_categories || 0) + ' {'albums created'|@translate|escape:'javascript'}';
      } else if (total > 0) {
        if (job.status === 'completed') {
          percent = 100;
        }
        progressMeta.textContent = processed + ' / ' + total + ' {'albums completed'|@translate|escape:'javascript'} (' + percent + '%)';
      } else {
        progressMeta.textContent = '{'Waiting for first batch'|@translate|escape:'javascript'}';
      }

      progressFill.style.width = percent + '%';
    }

    function updateMetadataModeNote(mode) {
      var text = mode === 'all'
        ? '{'Metadata mode currently re-reads metadata for all files.'|@translate|escape:'javascript'}'
        : '{'Metadata mode currently only reads metadata for new files.'|@translate|escape:'javascript'}';
      setText('bsm-metadata-mode-note', text);
    }

    function updateJobUi(job) {
      if (!job) {
        setText('bsm-status', 'idle');
        setText('bsm-phase', '-');
        setText('bsm-processed', '0');
        setText('bsm-total', '0');
        setText('bsm-processed-label', '{'Albums processed'|@translate|escape:'javascript'}');
        setText('bsm-total-label', '{'Albums queued'|@translate|escape:'javascript'}');
        setText('bsm-current-message', '{'No active job'|@translate|escape:'javascript'}');
        updateMetadataModeNote(document.getElementById('bsm-metadata-mode').value);
        renderLiveEntries([]);
        updateProgress(null);
        return;
      }

      showAlert('');
      setText('bsm-status', job.status);
      setText('bsm-phase', job.phase);
      if (job.phase === 'directory_scan') {
        setText('bsm-processed', String(job.scanned_directories || 0));
        setText('bsm-total', String(job.remaining_directories || 0));
        setText('bsm-processed-label', '{'Directories scanned'|@translate|escape:'javascript'}');
        setText('bsm-total-label', '{'Directories remaining'|@translate|escape:'javascript'}');
      } else {
        setText('bsm-processed', String(job.processed_categories || 0));
        setText('bsm-total', String(job.total_categories || 0));
        setText('bsm-processed-label', '{'Albums processed'|@translate|escape:'javascript'}');
        setText('bsm-total-label', '{'Albums queued'|@translate|escape:'javascript'}');
      }
      setText('bsm-current-message', job.current_message || '{'No active job'|@translate|escape:'javascript'}');
      updateMetadataModeNote(job.metadata_mode || 'new_only');
      renderLiveEntries(job.recent_log_entries || []);
      updateProgress(job);
    }

    function postApi(jobAction, extraData) {
      var formData = new FormData();
      formData.append('pwg_token', token);
      formData.append('job_action', jobAction);

      if (extraData) {
        Object.keys(extraData).forEach(function(key) {
          formData.append(key, extraData[key]);
        });
      }

      return fetch(adminUrl + '&mode=api', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      }).then(function(response) {
        return response.text().then(function(text) {
          var data = null;

          try {
            data = JSON.parse(text);
          } catch (error) {
            throw new Error(text || 'Invalid JSON response');
          }

          if (!response.ok) {
            throw new Error((data && data.message) || text || ('HTTP ' + response.status));
          }

          return data;
        });
      });
    }

    function runNextStep() {
      if (!activeJob || isProcessing) {
        return;
      }

      isProcessing = true;
      postApi('next_step').then(function(response) {
        isProcessing = false;

        if (!response.success || !response.payload) {
          showAlert(response.message || '{'The next batch request failed.'|@translate|escape:'javascript'}');
          return;
        }

        updateJobUi(response.job);

        if (response.payload.done) {
          activeJob = 0;
          return;
        }

        if (response.payload.step.type === 'internal') {
          window.setTimeout(runNextStep, 80);
          return;
        }
      }).catch(function() {
        isProcessing = false;
        showAlert('{'The next batch request failed.'|@translate|escape:'javascript'}');
      });
    }

    function startJob() {
      var siteId = document.getElementById('bsm-site').value;
      var rootCategoryId = document.getElementById('bsm-root-category').value;
      var metadataMode = document.getElementById('bsm-metadata-mode').value;

      postApi('start', {
        site_id: siteId,
        root_category_id: rootCategoryId,
        metadata_mode: metadataMode
      }).then(function(response) {
        if (!response.success || !response.job) {
          showAlert(response.message || '{'The bulk sync job could not be started.'|@translate|escape:'javascript'}');
          return;
        }

        activeJob = response.job.id;
        updateJobUi(response.job);
        runNextStep();
      }).catch(function(error) {
        activeJob = 0;
        showAlert(error && error.message ? error.message : '{'The bulk sync job could not be started.'|@translate|escape:'javascript'}');
      });
    }

    function cancelJob() {
      postApi('cancel').then(function() {
        activeJob = 0;
        updateJobUi(null);
      }).catch(function(error) {
        activeJob = 0;
        showAlert(error && error.message ? error.message : '{'The current job could not be cancelled.'|@translate|escape:'javascript'}');
      });
    }

    document.getElementById('bsm-start').addEventListener('click', startJob);
    document.getElementById('bsm-cancel').addEventListener('click', cancelJob);
    document.getElementById('bsm-metadata-mode').addEventListener('change', function() {
      if (!activeJob) {
        updateMetadataModeNote(this.value);
      }
    });
    document.getElementById('bsm-site').addEventListener('change', function() {
      if (activeJob) {
        return;
      }

      var metadataMode = document.getElementById('bsm-metadata-mode').value;
      window.location.href = adminUrl + '&site_id=' + encodeURIComponent(this.value) + '&metadata_mode=' + encodeURIComponent(metadataMode);
    });

    if (activeJob) {
      renderLiveEntries([
        {if isset($bsm_active_job) and !empty($bsm_active_job.recent_log_entries)}
          {foreach from=$bsm_active_job.recent_log_entries item=entry name=recent}
            {ldelim}time: '{$entry.time|escape:'javascript'}', message: '{$entry.message|escape:'javascript'}'{rdelim}{if not $smarty.foreach.recent.last},{/if}
          {/foreach}
        {/if}
      ]);
      runNextStep();
    } else {
      updateMetadataModeNote(document.getElementById('bsm-metadata-mode').value);
      renderLiveEntries([]);
    }
  })();
</script>
