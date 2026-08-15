<template>
  <div class="page">

    <PageHeader title="Reports & Analytics" subtitle="Platform-wide performance data">
      <template #actions>
        <select v-model="period" class="filter-select">
          <option value="month">This Month</option>
          <option value="quarter">This Quarter</option>
          <option value="year">This Year</option>
          <option value="all">All Time</option>
        </select>
        <button class="btn-export" :disabled="exporting" @click="exportPdf">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          {{ exporting ? 'Generating…' : 'Export PDF' }}
        </button>
      </template>
    </PageHeader>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <Spinner :size="22" />
      <span>Loading report data…</span>
    </div>

    <template v-else>
      <!-- KPI Row -->
      <div class="kpi-row" id="report-kpis">
        <StatCard
          v-for="k in kpis" :key="k.label"
          layout="horizontal"
          :icon="k.icon"
          :value="k.value"
          :label="k.label"
          :bg="k.bg"
          :col="k.col"
          :change="k.change"
          :up="k.up"
        />
      </div>

      <!-- Charts Row -->
      <div class="charts-row">

        <!-- Monthly Bar Chart -->
        <div class="chart-card wide" id="report-monthly">
          <div class="chart-header">
            <div>
              <h3>Monthly Donations</h3>
              <p>Total donations collected per month (₱ thousands)</p>
            </div>
          </div>
          <div v-if="monthlyData.length" class="bar-chart">
            <div class="bar-group" v-for="m in monthlyData" :key="m.month">
              <div class="bar-wrap">
                <div class="bar-tooltip">₱{{ m.amount }}K</div>
                <div class="bar" :style="{
                  height: (m.amount / maxAmount * 100) + '%',
                  background: m.month === currentMonth ? '#D4AF37' : '#0F2D52'
                }"></div>
              </div>
              <span class="bar-label">{{ m.month }}</span>
            </div>
          </div>
          <div v-else class="empty-chart">No donation data yet</div>
        </div>

        <!-- Donut Chart -->
        <div class="chart-card" id="report-donut">
          <div class="chart-header">
            <div>
              <h3>Donations by Category</h3>
              <p>Distribution breakdown</p>
            </div>
          </div>
          <div v-if="donutSegs.length" >
            <div class="donut-wrap">
              <svg viewBox="0 0 100 100" class="donut-svg">
                <circle cx="50" cy="50" r="35" fill="transparent" stroke="#f1f5f9" stroke-width="18"/>
                <circle
                  v-for="seg in donutSegs" :key="seg.cat"
                  cx="50" cy="50" r="35" fill="transparent"
                  :stroke="seg.color" stroke-width="18"
                  :stroke-dasharray="`${seg.dash} ${circumference}`"
                  :stroke-dashoffset="seg.offset"
                  stroke-linecap="butt"
                />
              </svg>
              <div class="donut-center">
                <strong>{{ totalRaisedLabel }}</strong>
                <span>total</span>
              </div>
            </div>
            <div class="donut-legend">
              <div class="leg-item" v-for="seg in donutSegs" :key="seg.cat">
                <div class="leg-dot" :style="{ background: seg.color }"></div>
                <span class="leg-label">{{ seg.cat }}</span>
                <span class="leg-pct">{{ seg.pct }}%</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-chart">No category data yet</div>
        </div>
      </div>

      <!-- Tables Row -->
      <div class="tables-row" id="report-tables">

        <!-- Top Donors -->
        <div class="table-card">
          <div class="card-header">
            <h3>Top Donors</h3>
            <p>Highest contributors this period</p>
          </div>
          <table v-if="topDonors.length">
            <thead><tr><th>#</th><th>Donor</th><th>Donations</th><th>Total</th></tr></thead>
            <tbody>
              <tr v-for="(d, i) in topDonors" :key="d.name">
                <td><span class="rank-badge">{{ i + 1 }}</span></td>
                <td>
                  <div class="user-mini">
                    <AvatarInitials :name="d.name" :color="d.color" :size="30" />
                    <span>{{ d.name }}</span>
                  </div>
                </td>
                <td>{{ d.count }}</td>
                <td><strong>{{ d.amount }}</strong></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="empty-table">No donor data yet</div>
        </div>

        <!-- Top Foundations -->
        <div class="table-card">
          <div class="card-header">
            <h3>Top Foundations</h3>
            <p>Most active this period</p>
          </div>
          <table v-if="topFoundations.length">
            <thead><tr><th>#</th><th>Foundation</th><th>Campaigns</th><th>Raised</th></tr></thead>
            <tbody>
              <tr v-for="(f, i) in topFoundations" :key="f.name">
                <td><span class="rank-badge">{{ i + 1 }}</span></td>
                <td>
                  <div class="user-mini">
                    <AvatarInitials :name="f.name" :color="f.color" :size="30" />
                    <span>{{ f.name }}</span>
                  </div>
                </td>
                <td>{{ f.campaigns }}</td>
                <td><strong>{{ f.raised }}</strong></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="empty-table">No foundation data yet</div>
        </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import PageHeader from '../components/superadmin/pageheader.vue'
import StatCard from '../components/superadmin/statcard.vue'
import Spinner from '../components/superadmin/spinner.vue'
import AvatarInitials from '../components/superadmin/avatarinitials.vue'

const period      = ref('month')
const currentMonth = new Date().toLocaleString('en', { month: 'short' })
const circumference = 2 * Math.PI * 35

const loading   = ref(true)
const exporting = ref(false)

const kpis           = ref([])
const monthlyData    = ref([])
const donutData      = ref([])
const topDonors      = ref([])
const topFoundations = ref([])

/* ── Fetch ── */
async function fetchReports() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/reports', { params: { period: period.value } })
    kpis.value           = data.kpis           ?? []
    monthlyData.value    = data.monthly         ?? []
    donutData.value      = data.categories      ?? []
    topDonors.value      = data.top_donors      ?? []
    topFoundations.value = data.top_foundations ?? []
  } catch (err) {
    console.error('Failed to load reports:', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchReports)
watch(period, fetchReports)

/* ── Derived ── */
const maxAmount = computed(() =>
  monthlyData.value.length ? Math.max(...monthlyData.value.map(m => m.amount || 0), 1) : 1
)

const totalRaisedLabel = computed(() => {
  const kpi = kpis.value.find(k => k.label === 'Total Raised')
  return kpi ? kpi.value : '—'
})

const donutSegs = computed(() => {
  let offset = 0
  return donutData.value.map(d => {
    const dash = ((d.pct || 0) / 100) * circumference
    const seg  = { ...d, dash, offset: circumference - offset }
    offset += dash
    return seg
  })
})

/* ── Script loader (for UMD builds via CDN) ── */
function loadScript(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) return resolve()
    const script = document.createElement('script')
    script.src = src
    script.onload = resolve
    script.onerror = () => reject(new Error(`Failed to load script: ${src}`))
    document.head.appendChild(script)
  })
}

/* ── Peso-symbol fix ──
   jsPDF's built-in fonts (Helvetica etc.) don't include the ₱ glyph,
   which corrupts the whole string when rendered. Swap it for "PHP "
   only inside the PDF — the on-screen UI keeps ₱ as-is. */
function peso(str) {
  return String(str).replace(/₱/g, 'PHP ')
}

/* ── PDF Export ── */
async function exportPdf() {
  exporting.value = true
  try {
    // Load UMD builds (bundle their own deps, no bare-specifier issues)
    await loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js')
    await loadScript('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js')

    const jsPDF = window.jspdf.jsPDF
    const html2canvas = window.html2canvas

    const doc    = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW  = doc.internal.pageSize.getWidth()
    const pageH  = doc.internal.pageSize.getHeight()
    const margin = 14
    let   y      = margin

    /* Header */
    doc.setFillColor(15, 45, 82)
    doc.rect(0, 0, pageW, 22, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('FoundationLink — Reports & Analytics', margin, 14)
    doc.setFontSize(8)
    doc.setFont('helvetica', 'normal')
    const periodLabel = { month: 'This Month', quarter: 'This Quarter', year: 'This Year', all: 'All Time' }
    doc.text(`Period: ${periodLabel[period.value]}   Generated: ${new Date().toLocaleDateString('en-PH', { dateStyle: 'long' })}`, pageW - margin, 14, { align: 'right' })
    y = 30

    /* KPIs */
    doc.setTextColor(15, 45, 82)
    doc.setFontSize(10)
    doc.setFont('helvetica', 'bold')
    doc.text('Key Performance Indicators', margin, y)
    y += 6

    const kpiBoxW = (pageW - margin * 2 - 9) / 4
    kpis.value.forEach((k, i) => {
      const x = margin + i * (kpiBoxW + 3)
      doc.setFillColor(248, 250, 252)
      doc.roundedRect(x, y, kpiBoxW, 22, 2, 2, 'F')
      doc.setTextColor(15, 45, 82)
      doc.setFontSize(11)
      doc.setFont('helvetica', 'bold')
      doc.text(peso(k.value), x + kpiBoxW / 2, y + 9, { align: 'center' })
      doc.setFontSize(7)
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(100, 116, 139)
      doc.text(peso(k.label), x + kpiBoxW / 2, y + 15, { align: 'center' })
      doc.setTextColor(k.up ? '#059669' : '#dc2626')
      doc.text(peso(k.change), x + kpiBoxW / 2, y + 20, { align: 'center' })
    })
    y += 30

    /* Monthly chart screenshot */
    const monthlyEl = document.getElementById('report-monthly')
    if (monthlyEl) {
      doc.setTextColor(15, 45, 82)
      doc.setFontSize(10)
      doc.setFont('helvetica', 'bold')
      doc.text('Monthly Donations', margin, y)
      y += 4
      const canvas = await html2canvas(monthlyEl, { scale: 2, backgroundColor: '#ffffff' })
      const imgData = canvas.toDataURL('image/png')
      const imgW    = pageW - margin * 2
      const imgH    = (canvas.height / canvas.width) * imgW
      doc.addImage(imgData, 'PNG', margin, y, imgW, imgH)
      y += imgH + 8
    }

    /* Top Donors table */
    if (topDonors.value.length) {
      if (y + 40 > pageH - margin) { doc.addPage(); y = margin }
      doc.setTextColor(15, 45, 82)
      doc.setFontSize(10)
      doc.setFont('helvetica', 'bold')
      doc.text('Top Donors', margin, y)
      y += 5

      const cols  = ['#', 'Donor', 'Donations', 'Total']
      const colXs = [margin, margin + 8, margin + 90, margin + 115]
      doc.setFillColor(15, 45, 82)
      doc.rect(margin, y, pageW - margin * 2, 7, 'F')
      doc.setTextColor(255, 255, 255)
      doc.setFontSize(8)
      cols.forEach((c, i) => doc.text(c, colXs[i] + 1, y + 5))
      y += 7

      topDonors.value.forEach((d, idx) => {
        doc.setFillColor(idx % 2 === 0 ? 248 : 255, idx % 2 === 0 ? 250 : 255, idx % 2 === 0 ? 252 : 255)
        doc.rect(margin, y, pageW - margin * 2, 7, 'F')
        doc.setTextColor(71, 85, 105)
        doc.setFont('helvetica', 'normal')
        doc.text(String(idx + 1), colXs[0] + 1, y + 5)
        doc.text(peso(d.name), colXs[1] + 1, y + 5)
        doc.text(String(d.count), colXs[2] + 1, y + 5)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(15, 45, 82)
        doc.text(peso(d.amount), colXs[3] + 1, y + 5)
        y += 7
      })
      y += 6
    }

    /* Top Foundations table */
    if (topFoundations.value.length) {
      if (y + 40 > pageH - margin) { doc.addPage(); y = margin }
      doc.setTextColor(15, 45, 82)
      doc.setFontSize(10)
      doc.setFont('helvetica', 'bold')
      doc.text('Top Foundations', margin, y)
      y += 5

      const cols  = ['#', 'Foundation', 'Campaigns', 'Raised']
      const colXs = [margin, margin + 8, margin + 90, margin + 115]
      doc.setFillColor(15, 45, 82)
      doc.rect(margin, y, pageW - margin * 2, 7, 'F')
      doc.setTextColor(255, 255, 255)
      doc.setFontSize(8)
      cols.forEach((c, i) => doc.text(c, colXs[i] + 1, y + 5))
      y += 7

      topFoundations.value.forEach((f, idx) => {
        doc.setFillColor(idx % 2 === 0 ? 248 : 255, idx % 2 === 0 ? 250 : 255, idx % 2 === 0 ? 252 : 255)
        doc.rect(margin, y, pageW - margin * 2, 7, 'F')
        doc.setTextColor(71, 85, 105)
        doc.setFont('helvetica', 'normal')
        doc.text(String(idx + 1), colXs[0] + 1, y + 5)
        doc.text(peso(f.name), colXs[1] + 1, y + 5)
        doc.text(String(f.campaigns), colXs[2] + 1, y + 5)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(15, 45, 82)
        doc.text(peso(f.raised), colXs[3] + 1, y + 5)
        y += 7
      })
    }

    /* Footer */
    const pages = doc.internal.getNumberOfPages()
    for (let i = 1; i <= pages; i++) {
      doc.setPage(i)
      doc.setFontSize(7)
      doc.setTextColor(148, 163, 184)
      doc.setFont('helvetica', 'normal')
      doc.text(`Page ${i} of ${pages}  •  FoundationLink Admin`, pageW / 2, pageH - 6, { align: 'center' })
    }

    doc.save(`foundationlink-report-${period.value}-${Date.now()}.pdf`)
  } catch (err) {
    console.error('PDF export failed:', err)
    alert('PDF export failed. Please try again.')
  } finally {
    exporting.value = false
  }
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

.filter-select { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.85rem; background: white; color: #0F2D52; outline: none; cursor: pointer; }

.btn-export { display: flex; align-items: center; gap: 7px; padding: 9px 16px; background: #0F2D52; color: white; border: none; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-export:hover:not(:disabled) { background: #1a3f6b; }
.btn-export:disabled { opacity: 0.6; cursor: not-allowed; }

/* Loading */
.loading-state { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 80px 0; color: #94a3b8; font-size: 0.9rem; }

/* KPIs */
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }

/* Charts */
.charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 20px; }
.chart-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); }
.chart-header h3 { font-size: 0.95rem; color: #0F2D52; margin: 0 0 3px; }
.chart-header p  { font-size: 0.78rem; color: #94a3b8; margin: 0 0 20px; }

.bar-chart  { display: flex; align-items: flex-end; gap: 12px; height: 140px; }
.bar-group  { display: flex; flex-direction: column; align-items: center; gap: 6px; flex: 1; height: 100%; }
.bar-wrap   { flex: 1; width: 100%; display: flex; align-items: flex-end; position: relative; }
.bar-wrap:hover .bar-tooltip { opacity: 1; }
.bar-tooltip { position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); background: #0F2D52; color: white; font-size: 0.7rem; padding: 3px 7px; border-radius: 6px; white-space: nowrap; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
.bar       { width: 100%; border-radius: 6px 6px 0 0; min-height: 4px; transition: height 0.5s ease; }
.bar-label { font-size: 0.72rem; color: #94a3b8; }

.donut-wrap   { position: relative; width: 140px; height: 140px; margin: 0 auto 14px; }
.donut-svg    { width: 100%; height: 100%; transform: rotate(-90deg); }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.donut-center strong { font-size: 0.85rem; font-weight: 800; color: #0F2D52; }
.donut-center span   { font-size: 0.7rem; color: #94a3b8; }

.donut-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.leg-item  { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; }
.leg-dot   { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.leg-label { flex: 1; color: #475569; }
.leg-pct   { font-weight: 700; color: #0F2D52; }

.empty-chart { display: flex; align-items: center; justify-content: center; height: 120px; color: #cbd5e1; font-size: 0.85rem; }

/* Tables */
.tables-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.table-card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }

.card-header   { padding: 18px 18px 0; margin-bottom: 12px; }
.card-header h3 { font-size: 0.92rem; color: #0F2D52; margin: 0 0 2px; }
.card-header p  { font-size: 0.77rem; color: #94a3b8; margin: 0; }

table       { width: 100%; border-collapse: collapse; }
thead th    { padding: 10px 16px; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #f1f5f9; }
tbody td    { padding: 10px 16px; font-size: 0.83rem; color: #475569; border-bottom: 1px solid #f8fafc; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }
tbody td strong { color: #0F2D52; }

.rank-badge { width: 22px; height: 22px; background: #f1f5f9; color: #64748b; font-size: 0.76rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; }
.user-mini  { display: flex; align-items: center; gap: 8px; }
.empty-table { padding: 24px 18px; color: #cbd5e1; font-size: 0.85rem; text-align: center; }
</style>