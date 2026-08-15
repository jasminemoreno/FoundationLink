<template>
  <div class="landing-wrapper">

    <!-- Header -->
    <div class="header">
      <h1 class="main-title">
        Together, We Can <span class="title-green">Make a Difference</span>
      </h1>

      <p class="main-subtitle">
        FoundationLink connects generous donors with trusted foundations<br />
        to support meaningful causes and create lasting impact
      </p>
    </div>

    <!-- Cards Row -->
    <div class="cards-row">

      <!-- ── Donor Card ── -->
      <div class="card card-donor">

        <div class="card-icon-wrap icon-donor">
          <img src="../js/assets/icons/donor.png" class="card-icon-img" />
        </div>

        <h2 class="card-title">I want to donate</h2>

        <p class="card-desc">
          Support campaigns, donate money or items,<br />
          and help improve lives in communities
        </p>

        <div class="card-illustration">
          <img src="../js/assets/img/donor-image.png" class="illustration-img" />
        </div>

        <ul class="feature-list">
          <li class="feature-item">
            <span class="check-icon check-green">
              <CheckCircleIcon />
            </span>
            Browse and support meaningful campaigns
          </li>

          <li class="feature-item">
            <span class="check-icon check-green">
              <CheckCircleIcon />
            </span>
            Donate money or essential items
          </li>

          <li class="feature-item">
            <span class="check-icon check-green">
              <CheckCircleIcon />
            </span>
            Track your donation and impact
          </li>
        </ul>

        <button class="card-btn btn-donor" @click="goTo('donor')">
          <svg class="btn-icon" viewBox="0 0 24 24" fill="none">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
              stroke="currentColor" stroke-width="2" />
          </svg>

          Continue as Donor
        </button>

      </div>

      <!-- ── Center Logo ── -->
      <div class="center-badge">
        <img src="../js/assets/img/logo.png" class="center-logo-img" />
      </div>

      <!-- ── Admin Card ── -->
      <div class="card card-admin">

        <div class="card-icon-wrap icon-admin">
          <img src="../js/assets/icons/admin.png" class="card-icon-img" />
        </div>

        <h2 class="card-title">I'm a Foundation Admin</h2>

        <p class="card-desc">
          Create and manage campaigns, receive donations,<br />
          and make a greater impact
        </p>

        <div class="card-illustration">
          <img src="../js/assets/img/admin-image.png" class="illustration-img" />
        </div>

        <ul class="feature-list">
          <li class="feature-item">
            <span class="check-icon check-blue">
              <CheckCircleIcon />
            </span>
            Create and manage fundraising campaigns
          </li>

          <li class="feature-item">
            <span class="check-icon check-blue">
              <CheckCircleIcon />
            </span>
            Receive and manage donations
          </li>

          <li class="feature-item">
            <span class="check-icon check-blue">
              <CheckCircleIcon />
            </span>
            Update donors and track progress
          </li>
        </ul>

        <button class="card-btn btn-admin" @click="goTo('admin')">
          <svg class="btn-icon" viewBox="0 0 24 24" fill="none">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
              stroke="currentColor" stroke-width="2" />
          </svg>

          Continue as Foundation Admin
        </button>

        <p class="already-account">
          Already have an account?
          <router-link to="/login" class="login-link">
            Log In
          </router-link>
        </p>

      </div>

    </div>
  </div>
</template>

<script>
const CheckCircleIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor" width="17" height="17">
      <path fill-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9
        10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
    </svg>
  `
}

export default {
  name: "RoleChoosePage",

  components: {
    CheckCircleIcon
  },

  methods: {
    goTo(role) {
      if (role === "donor") {
        this.$router.push("/donor/register")
      } else {
        this.$router.push("/foundation/register")
      }
    }
  }
}
</script>

  <style scoped>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  
  /* ── Page background ── */
  .landing-wrapper {
    min-height: 100vh;
    background: #e8f4fb;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem 4rem;
    font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
  }
  
  /* ── Header ── */
  .header {
    text-align: center;
    margin-bottom: 2.75rem;
  }
  
  .main-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.3;
    margin-bottom: 0.7rem;
  }
  
  .title-green {
    color: #27a85f;
  }
  
  .main-subtitle {
    font-size: 0.92rem;
    color: #5a5a72;
    line-height: 1.75;
  }
  
  /* ── Cards row ── */
  .cards-row {
    display: flex;
    align-items: stretch;
    justify-content: center;
    width: 100%;
    max-width: 900px;
    position: relative;
    gap: 0;
  }
  
  /* ── Individual card ── */
  .card {
    flex: 1;
    border-radius: 20px;
    padding: 2.25rem 2rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    padding-top: 3.5rem;
  }
  
  .card-donor {
    background: #d5f0e3;
  }
  
  .card-admin {
    background: #d4e8f8;
  }
  
  /* ── Floating icon circle ── */
  .card-icon-wrap {
    position: absolute;
    top: -2.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 78px;
    height: 78px;
    border-radius: 50%;
    border: 5px solid #e8f4fb;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .icon-donor {
    background: #edfaf3;
  }
  
  .icon-admin {
    background: #e8f2fb;
  }
  
  .card-icon-img {
    width: 42px;
    height: 42px;
    object-fit: contain;
  }
  
  /* ── Card text ── */
  .card-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 0.45rem;
  }
  
  .card-desc {
    font-size: 0.8rem;
    color: #555570;
    line-height: 1.65;
    margin-bottom: 1.25rem;
  }
  
  /* ── Illustration box ── */
  .card-illustration {
    width: 100%;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.2rem;
    background: rgba(255, 255, 255, 0.4);
    min-height: 148px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .illustration-img {
    width: 100%;
    height: 148px;
    object-fit: cover;
    border-radius: 14px;
    display: block;
  }
  
  /* ── Feature list ── */
  .feature-list {
    list-style: none;
    width: 100%;
    text-align: left;
    margin-bottom: 1.5rem;
  }
  
  .feature-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.82rem;
    color: #333348;
    padding: 4px 0;
    line-height: 1.55;
  }
  
  .check-icon {
    flex-shrink: 0;
    margin-top: 1px;
    display: flex;
  }
  
  .check-green {
    color: #27a85f;
  }
  
  .check-blue {
    color: #1b6fbf;
  }
  
  /* ── Buttons ── */
  .card-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 20px;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    letter-spacing: 0.01em;
    transition: opacity 0.18s ease, transform 0.15s ease;
  }
  
  .card-btn:hover {
    opacity: 0.88;
    transform: translateY(-1px);
  }
  
  .card-btn:active {
    transform: scale(0.98);
  }
  
  .btn-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
  }
  
  .btn-donor {
    background: #27a85f;
    color: #ffffff;
  }
  
  .btn-admin {
    background: #1b6fbf;
    color: #ffffff;
  }
  
  /* ── Already have account ── */
  .already-account {
    font-size: 0.78rem;
    color: #777790;
    margin-top: 0.9rem;
  }
  
  .login-link {
    color: #1b6fbf;
    font-weight: 700;
    cursor: default;
  }
  
  /* ── Center badge (logo between cards) ── */
  .center-badge {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    align-self: center;
    z-index: 10;
    margin: 0 -2px;
  }
  
  .center-logo-img {
    width: 54px;
    height: 54px;
    object-fit: contain;
  }
  
  /* ── Responsive ── */
  @media (max-width: 700px) {
    .cards-row {
      flex-direction: column;
      gap: 3.5rem;
    }
  
    .card {
      width: 100%;
      margin: 0 !important;
    }
  
    .center-badge {
      align-self: center;
      margin: 0;
    }
  
    .main-title {
      font-size: 1.45rem;
    }
  }
  </style>