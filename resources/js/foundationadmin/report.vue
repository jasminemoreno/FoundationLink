<template>
  <div class="page">

    <PageHeader title="Reports" subtitle="Overview of your foundation's performance" />

    <!-- PENDING VERIFICATION BANNER -->
    <PendingVerificationBanner v-if="!isVerified && !checkingStatus">
      Reports will be available here once your foundation has been verified by the super admin.
    </PendingVerificationBanner>

    <!-- LOADING -->
    <EmptyState v-else-if="isLoading" loading message="Loading reports..." />

    <div v-else>

      <!-- EXPORT TOOLBAR -->
      <div class="toolbar">
        <button class="export-btn" :disabled="isExporting" @click="exportPdf">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          {{ isExporting ? 'Generating PDF...' : 'Export PDF' }}
        </button>
      </div>

      <!-- SUMMARY CARDS -->
      <div class="stats-row">
        <StatCard
          v-for="s in summaryCards" :key="s.label"
          :icon="s.icon" :value="s.value" :label="s.label"
          :bg="s.bg" :col="s.col" :icon-size="40"
        />
      </div>

      <!-- MONTHLY BREAKDOWN -->
      <div class="panel" style="margin-bottom: 20px;">
        <div class="panel-head">
          <h3 class="panel-title">Monthly Breakdown (Last 6 Months)</h3>
        </div>
        <div class="panel-body">
          <EmptyState v-if="monthly.length === 0" message="No data yet." compact />
          <div v-else class="monthly-grid">
            <div v-for="m in monthly" :key="m.month" class="month-card">
              <div class="month-label">{{ m.month }}</div>
              <div class="month-raised">₱{{ formatMoney(m.monetary) }}</div>
              <div class="month-meta">
                <span class="meta-item monetary">₱{{ formatMoney(m.monetary) }} raised</span>
                <span class="meta-item item">{{ m.items }} items</span>
                <span class="meta-item total">{{ m.total }} total</span>
              </div>
              <div class="month-bar">
                <div
                  class="month-bar-fill"
                  :style="{ height: monthBarHeight(m.monetary) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PER CAMPAIGN -->
      <div class="panel">
        <div class="panel-head">
          <h3 class="panel-title">Campaign Performance</h3>
        </div>
        <div class="panel-body">
          <EmptyState v-if="perCampaign.length === 0" icon="📋" message="No campaigns yet." compact />
          <table v-else class="report-table">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Status</th>
                <th>Type</th>
                <th>Goal</th>
                <th>Raised</th>
                <th>Progress</th>
                <th>Items</th>
                <th>Donors</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in perCampaign" :key="c.id">
                <td class="campaign-name">{{ c.title }}</td>
                <td>
                  <Badge :label="statusLabel(c.status)" :tone="toneForCampaignStatus(c.status)" />
                </td>
                <td>
                  <Badge :label="c.type" :tone="toneForType(c.type)" />
                </td>
                <td>
                  <span v-if="c.type !== 'item'">₱{{ formatMoney(c.goal_amount) }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="raised-col">
                  <span v-if="c.type !== 'item'">₱{{ formatMoney(c.raised) }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td style="min-width: 140px;">
                  <div v-if="c.type !== 'item'" class="inline-progress">
                    <div class="inline-bar">
                      <div
                        class="inline-fill"
                        :style="{ width: c.percent + '%', background: progressColor(c.percent) }"
                      ></div>
                    </div>
                    <span class="inline-pct">{{ c.percent }}%</span>
                  </div>
                  <span v-else class="muted">—</span>
                </td>
                <td class="center">{{ c.items }}</td>
                <td class="center">{{ c.donors }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import api from "@/services/api"
import PageHeader from "../components/foundation-admin/pageheader.vue"
import PendingVerificationBanner from "../components/foundation-admin/pendingverificationbanner.vue"
import StatCard from "../components/foundation-admin/statcard.vue"
import EmptyState from "../components/foundation-admin/emptystate.vue"
import Badge from "../components/foundation-admin/badge.vue"

const isLoading  = ref(false)
const summary    = ref({})
const monthly    = ref([])
const perCampaign = ref([])

const isVerified     = ref(false)
const checkingStatus = ref(true)

const isExporting    = ref(false)
const foundationName = ref("")

/* ── FOUNDATION STATUS ── */
async function loadFoundationStatus() {
  checkingStatus.value = true
  try {
    const res = await api.get("/foundation/dashboard")
    isVerified.value = res.data?.foundation?.status === 'verified'
    foundationName.value = res.data?.foundation?.name || ""
  } catch (err) {
    console.error("Failed to load foundation status:", err)
    isVerified.value = false
  } finally {
    checkingStatus.value = false
  }
}

async function load() {
  isLoading.value = true
  try {
    const res = await api.get("/foundation/reports")
    summary.value     = res.data.summary    || {}
    monthly.value     = res.data.monthly    || []
    perCampaign.value = res.data.per_campaign || []
  } catch (err) {
    console.error("Failed to load reports:", err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadFoundationStatus()
  if (isVerified.value) load()
})

const summaryCards = computed(() => [
  {
    label: "Total Raised",
    value: "₱" + formatMoney(summary.value.total_raised),
    bg: "#f0fdf4", col: "#059669",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`
  },
  {
    label: "Total Donations",
    value: summary.value.total_donations ?? 0,
    bg: "#eff6ff", col: "#3b82f6",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
  },
  {
    label: "Total Donors",
    value: summary.value.total_donors ?? 0,
    bg: "#f5f3ff", col: "#7c3aed",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>`
  },
  {
    label: "Item Donations",
    value: summary.value.total_items ?? 0,
    bg: "#fefce8", col: "#ca8a04",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`
  },
  {
    label: "Total Campaigns",
    value: summary.value.total_campaigns ?? 0,
    bg: "#fdf2f8", col: "#db2777",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>`
  },
  {
    label: "Active Campaigns",
    value: summary.value.active_campaigns ?? 0,
    bg: "#ecfdf5", col: "#10b981",
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`
  },
])

function monthBarHeight(val) {
  const max = Math.max(...monthly.value.map(m => m.monetary), 1)
  return Math.round((val / max) * 100)
}

function formatMoney(v) { return Number(v || 0).toLocaleString() }

function progressColor(pct) {
  if (pct >= 80) return "#059669"
  if (pct >= 50) return "#0F2D52"
  return "#3b82f6"
}

function statusLabel(s) {
  return { active: "Active", completed: "Completed", draft: "Draft", cancelled: "Cancelled" }[s] || s
}

function toneForCampaignStatus(s) {
  if (s === 'active')    return 'success'
  if (s === 'completed') return 'info'
  if (s === 'cancelled') return 'danger'
  return 'neutral' // draft
}

function toneForType(t) {
  if (t === 'monetary') return 'success'
  if (t === 'item')     return 'info'
  return 'purple' // both
}

/* ── PDF EXPORT ── */
// jsPDF's built-in fonts have no ₱ glyph, so the PDF uses "PHP " instead
function peso(v) { return "PHP " + formatMoney(v) }
function pdfText(v) { return String(v ?? "").replace(/₱/g, "PHP ") }

async function exportPdf() {
  if (isExporting.value) return
  isExporting.value = true

  try {
    // UMD build (same approach as the superadmin export)
    const mod = await import("jspdf/dist/jspdf.umd.min.js")
    const JsPDF = mod.jsPDF || mod.default?.jsPDF || mod.default
    const doc = new JsPDF({ unit: "mm", format: "a4" })

    const PAGE_W = 210
    const PAGE_H = 297
    const M      = 14
    const CW     = PAGE_W - M * 2
    const BOTTOM = PAGE_H - 18

    const NAVY  = [15, 45, 82]
    const GOLD  = [212, 175, 55]
    const GRAY  = [100, 116, 139]
    const BODY  = [71, 85, 105]
    const LIGHT = [241, 245, 249]

    let y = 0

    function newPage() {
      doc.addPage()
      y = M
    }

    function sectionTitle(text) {
      if (y + 24 > BOTTOM) newPage()
      doc.setFont("helvetica", "bold")
      doc.setFontSize(12)
      doc.setTextColor(...NAVY)
      doc.text(text, M, y)
      y += 2.5
      doc.setDrawColor(226, 232, 240)
      doc.setLineWidth(0.3)
      doc.line(M, y, M + CW, y)
      y += 6
    }

    function emptyNote(text) {
      doc.setFont("helvetica", "italic")
      doc.setFontSize(9)
      doc.setTextColor(...GRAY)
      doc.text(text, M, y + 2)
      y += 12
    }

    function cellX(col, x) {
      if (col.align === "right")  return x + col.w - 2
      if (col.align === "center") return x + col.w / 2
      return x + 2
    }

    function drawTableHeader(cols) {
      doc.setFillColor(...NAVY)
      doc.rect(M, y, CW, 8, "F")
      doc.setFont("helvetica", "bold")
      doc.setFontSize(8)
      doc.setTextColor(255, 255, 255)
      let x = M
      cols.forEach(c => {
        doc.text(c.label.toUpperCase(), cellX(c, x), y + 5.2, { align: c.align || "left" })
        x += c.w
      })
      y += 8
    }

    function drawTable(cols, rows) {
      if (y + 8 + 12 > BOTTOM) newPage()
      drawTableHeader(cols)

      rows.forEach((row, i) => {
        doc.setFont("helvetica", "normal")
        doc.setFontSize(8.5)

        const lines = cols.map((c, ci) => doc.splitTextToSize(pdfText(row[ci]), c.w - 4))
        const maxLines = Math.max(...lines.map(l => l.length))
        const h = maxLines * 3.8 + 4

        if (y + h > BOTTOM) {
          newPage()
          drawTableHeader(cols)
        }

        if (i % 2 === 0) {
          doc.setFillColor(...LIGHT)
          doc.rect(M, y, CW, h, "F")
        }

        doc.setFont("helvetica", "normal")
        doc.setFontSize(8.5)
        doc.setTextColor(...BODY)
        let x = M
        cols.forEach((c, ci) => {
          doc.text(lines[ci], cellX(c, x), y + 5, { align: c.align || "left" })
          x += c.w
        })
        y += h
      })

      y += 8
    }

    /* HEADER BAND */
    doc.setFillColor(...NAVY)
    doc.rect(0, 0, PAGE_W, 30, "F")
    doc.setFillColor(...GOLD)
    doc.rect(0, 30, PAGE_W, 1.2, "F")

    doc.setFont("helvetica", "bold")
    doc.setFontSize(18)
    doc.setTextColor(255, 255, 255)
    doc.text("Foundation Report", M, 14)

    doc.setFont("helvetica", "normal")
    doc.setFontSize(10)
    doc.text(pdfText(foundationName.value) || "Overview of your foundation's performance", M, 22)

    doc.setFontSize(9)
    doc.text(
      "Generated " + new Date().toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" }),
      PAGE_W - M, 14, { align: "right" }
    )

    y = 42

    /* SUMMARY */
    sectionTitle("Summary")
    const cards = summaryCards.value
    const cardGap = 4
    const cardW = (CW - cardGap * 2) / 3
    const cardH = 20
    cards.forEach((s, i) => {
      const col = i % 3
      const row = Math.floor(i / 3)
      const x = M + col * (cardW + cardGap)
      const cy = y + row * (cardH + cardGap)

      doc.setFillColor(...LIGHT)
      doc.roundedRect(x, cy, cardW, cardH, 2, 2, "F")

      doc.setFont("helvetica", "normal")
      doc.setFontSize(8)
      doc.setTextColor(...GRAY)
      doc.text(s.label.toUpperCase(), x + 4, cy + 7)

      doc.setFont("helvetica", "bold")
      doc.setFontSize(13)
      doc.setTextColor(...NAVY)
      doc.text(pdfText(s.value), x + 4, cy + 15)
    })
    y += Math.ceil(cards.length / 3) * (cardH + cardGap) + 6

    /* MONTHLY BREAKDOWN */
    sectionTitle("Monthly Breakdown (Last 6 Months)")
    if (monthly.value.length === 0) {
      emptyNote("No data yet.")
    } else {
      drawTable(
        [
          { label: "Month",  w: 62, align: "left" },
          { label: "Raised", w: 60, align: "right" },
          { label: "Items",  w: 30, align: "right" },
          { label: "Total",  w: 30, align: "right" },
        ],
        monthly.value.map(m => [m.month, peso(m.monetary), m.items ?? 0, m.total ?? 0])
      )
    }

    /* CAMPAIGN PERFORMANCE */
    sectionTitle("Campaign Performance")
    if (perCampaign.value.length === 0) {
      emptyNote("No campaigns yet.")
    } else {
      drawTable(
        [
          { label: "Campaign", w: 52, align: "left" },
          { label: "Status",   w: 20, align: "left" },
          { label: "Type",     w: 18, align: "left" },
          { label: "Goal",     w: 26, align: "right" },
          { label: "Raised",   w: 26, align: "right" },
          { label: "Progress", w: 14, align: "right" },
          { label: "Items",    w: 13, align: "right" },
          { label: "Donors",   w: 13, align: "right" },
        ],
        perCampaign.value.map(c => {
          const isItem = c.type === "item"
          return [
            c.title,
            statusLabel(c.status),
            c.type ? c.type.charAt(0).toUpperCase() + c.type.slice(1) : "",
            isItem ? "-" : peso(c.goal_amount),
            isItem ? "-" : peso(c.raised),
            isItem ? "-" : (c.percent ?? 0) + "%",
            c.items ?? 0,
            c.donors ?? 0,
          ]
        })
      )
    }

    /* PAGE FOOTERS */
    const pageCount = doc.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i)
      doc.setDrawColor(226, 232, 240)
      doc.setLineWidth(0.3)
      doc.line(M, PAGE_H - 13, M + CW, PAGE_H - 13)
      doc.setFont("helvetica", "normal")
      doc.setFontSize(8)
      doc.setTextColor(...GRAY)
      doc.text("FoundationLink - Foundation Admin Report", M, PAGE_H - 8)
      doc.text(`Page ${i} of ${pageCount}`, PAGE_W - M, PAGE_H - 8, { align: "right" })
    }

    const stamp = new Date().toISOString().slice(0, 10)
    doc.save(`foundation-report-${stamp}.pdf`)
  } catch (err) {
    console.error("Failed to export PDF:", err)
    alert("Could not generate the PDF. Please try again.")
  } finally {
    isExporting.value = false
  }
}
</script>

<style scoped>
.page { padding: 28px; background: #f8fafc; min-height: 100%; }

/* EXPORT BUTTON */
.toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.export-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 10px; background: #0F2D52; color: #fff; font-size: 0.83rem; font-weight: 700; cursor: pointer; transition: background 0.2s ease, opacity 0.2s ease; }
.export-btn:hover:not(:disabled) { background: #163d6e; }
.export-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.stats-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 20px; }

.panel { background: white; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(15,45,82,0.06); overflow: hidden; }
.panel-head  { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 12px; border-bottom: 1px solid #f1f5f9; }
.panel-title { font-size: 0.92rem; font-weight: 700; color: #0F2D52; margin: 0; }
.panel-body  { padding: 20px; }

/* MONTHLY GRID */
.monthly-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
.month-card   { background: #f8fafc; border-radius: 12px; padding: 14px 12px; display: flex; flex-direction: column; gap: 6px; }
.month-label  { font-size: 0.72rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
.month-raised { font-size: 1rem; font-weight: 800; color: #0F2D52; }
.month-meta   { display: flex; flex-direction: column; gap: 3px; }
.meta-item    { font-size: 0.7rem; font-weight: 500; }
.meta-item.monetary { color: #059669; }
.meta-item.item     { color: #3b82f6; }
.meta-item.total    { color: #94a3b8; }
.month-bar    { height: 60px; background: #e2e8f0; border-radius: 6px; overflow: hidden; display: flex; align-items: flex-end; margin-top: 4px; }
.month-bar-fill { width: 100%; background: linear-gradient(180deg, #3b82f6, #0F2D52); border-radius: 6px; transition: height 0.5s ease; min-height: 4px; }

/* TABLE */
.report-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.report-table th { text-align: left; padding: 10px 12px; color: #94a3b8; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #f1f5f9; }
.report-table td { padding: 12px; border-bottom: 1px solid #f8fafc; color: #475569; vertical-align: middle; }
.report-table tbody tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover { background: #f8fafc; }

.campaign-name { font-weight: 600; color: #0F2D52; max-width: 160px; }
.raised-col    { font-weight: 700; color: #059669; }
.center        { text-align: center; }
.muted         { color: #cbd5e1; }

.inline-progress { display: flex; align-items: center; gap: 8px; }
.inline-bar  { flex: 1; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.inline-fill { height: 100%; border-radius: 99px; transition: width 0.5s; }
.inline-pct  { font-size: 0.72rem; font-weight: 700; color: #0F2D52; white-space: nowrap; }
</style>