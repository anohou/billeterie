<template>
    <div v-if="shouldShow" class="pagination-container">
      <div class="pagination-wrapper">
        <div class="pagination-stats">
          {{ from }} à {{ to }} / {{ total }} 
        </div>
  
        <div class="pagination-controls">
          <!-- Items per page selector -->
          <select 
            v-model="localPerPage" 
            @change="changeItemsPerPage" 
            class="per-page-selector"
            aria-label="Nombre d'éléments par page"
          >
            <option v-for="size in pageSizes" :key="size" :value="size">
              {{ size }} par page
            </option>
          </select>
          <!-- Pagination navigation -->
          <div class="pagination-nav">
            <!-- Previous page button -->
            <button 
              @click="handlePageClick(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="pagination-button pagination-nav-button"
              aria-label="Page précédente"
            >
              <span aria-hidden="true">‹</span>
            </button>
  
            <!-- Page numbers -->
            <div class="pagination-pages">
              <button 
                v-for="page in visiblePageNumbers" 
                :key="page"
                @click="handlePageClick(page)"
                :class="[
                  'pagination-button', 
                  'pagination-page-button',
                  currentPage === page ? 'pagination-active' : ''
                ]"
              >
                {{ page }}
              </button>
  
              <!-- Ellipsis -->
              <span v-if="showLeftEllipsis" class="pagination-ellipsis">...</span>
              <span v-if="showRightEllipsis" class="pagination-ellipsis">...</span>
            </div>
  
            <!-- Next page button -->
            <button 
              @click="handlePageClick(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="pagination-button pagination-nav-button"
              aria-label="Page suivante"
            >
              <span aria-hidden="true">›</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref } from 'vue';
  
  const props = defineProps({
    links: {
      type: [Array, Object],
      default: () => []
    },
    meta: {
      type: Object,
      default: () => ({})
    },
    currentPage: {
      type: Number,
      default: 1
    },
    lastPage: {
      type: Number,
      default: 1
    },
    from: {
      type: Number,
      default: 0
    },
    to: {
      type: Number,
      default: 0
    },
    total: {
      type: Number,
      default: 0
    },
    perPage: {
      type: Number,
      default: 10
    },
    filters: {
      type: Object,
      default: () => ({
        arrondissement: '',
        sector: '',
        quartier: '',
        collector: '',
        search: ''
      })
    },
    filterOptions: {
      type: Object,
      default: () => ({
        arrondissements: [],
        sectors: [],
        quartiers: [],
        collectors: []
      })
    }
  });
  
  const emit = defineEmits(['pageChanged', 'perPageChanged']);
  
  // Available page sizes
  const pageSizes = [10, 25, 50, 100];
  const localPerPage = ref(props.perPage);
  
  // Determine if pagination should be shown
  const shouldShow = computed(() => {
    //return props.lastPage > 1;
    return true;
  });
  
  // Check if there are active filters
  const hasActiveFilters = computed(() => {
    return props.filters.arrondissement || 
           props.filters.sector || 
           props.filters.quartier || 
           props.filters.collector || 
           props.filters.search;
  });
  
  // Get human-readable label for filter values
  const getFilterLabel = (filterType, value) => {
    if (!value) return '';
    
    switch (filterType) {
      case 'arrondissement':
        const arrondissement = props.filterOptions.arrondissements.find(a => a.id === value);
        return arrondissement ? arrondissement.name : value;
      case 'sector':
        const sector = props.filterOptions.sectors.find(s => s.id === value);
        return sector ? sector.name : value;
      case 'quartier':
        const quartier = props.filterOptions.quartiers.find(q => q.id === value);
        return quartier ? quartier.name : value;
      case 'collector':
        const collector = props.filterOptions.collectors.find(c => c.id === value);
        return collector ? `${collector.first_name} ${collector.last_name}` : value;
      default:
        return value;
    }
  };
  
  // Logic for determining which page numbers to show
  const visiblePageNumbers = computed(() => {
    const showPages = [];
    const maxVisiblePages = 5; // Maximum number of page buttons to show
    
    if (props.lastPage <= maxVisiblePages) {
      // If we have fewer pages than the max, show them all
      for (let i = 1; i <= props.lastPage; i++) {
        showPages.push(i);
      }
    } else {
      // Always show first page
      showPages.push(1);
      
      // Calculate the range of pages to show around current page
      let startPage = Math.max(2, props.currentPage - 1);
      let endPage = Math.min(props.lastPage - 1, props.currentPage + 1);
      
      // Adjust if current page is at the beginning
      if (props.currentPage <= 3) {
        endPage = Math.min(maxVisiblePages - 1, props.lastPage - 1);
      }
      
      // Adjust if current page is at the end
      if (props.currentPage >= props.lastPage - 2) {
        startPage = Math.max(2, props.lastPage - maxVisiblePages + 2);
      }
      
      // Add the range of pages
      for (let i = startPage; i <= endPage; i++) {
        showPages.push(i);
      }
      
      // Always show last page
      if (props.lastPage > 1) {
        showPages.push(props.lastPage);
      }
    }
    
    return showPages;
  });
  
  // Determine if we need to show ellipsis
  const showLeftEllipsis = computed(() => {
    return visiblePageNumbers.value.length > 0 && 
           visiblePageNumbers.value[1] > 2;
  });
  
  const showRightEllipsis = computed(() => {
    return visiblePageNumbers.value.length > 0 && 
           visiblePageNumbers.value[visiblePageNumbers.value.length - 1] < props.lastPage - 1;
  });
  
  // Handle page click
  const handlePageClick = (page) => {
    if (page < 1 || page > props.lastPage) return;
    emit('pageChanged', page);
  };
  
  // Handle per page change
  const changeItemsPerPage = () => {
    emit('perPageChanged', localPerPage.value);
  };
  </script>
  
  <style scoped>
  .pagination-container {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid #f3e8d8;
    background-color: #f9f9f7;
  }
  
  .pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
  }
  
  @media (min-width: 768px) {
    .pagination-wrapper {
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
    }
  }
  
  .pagination-stats {
    font-size: 0.875rem;
    color: #666;
    white-space: nowrap;
  }
  
  .pagination-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  
  .per-page-selector {
    padding: 0.25rem 0.5rem;
    border: 1px solid #ddd;
    border-radius: 0.25rem;
    background-color: white;
    color: #4a7c59;
    font-size: 0.875rem;
    cursor: pointer;
  }
  
  .filter-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .filter-summary-text {
    font-size: 0.75rem;
    color: #666;
  }
  
  .filter-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .filter-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
  }
  
  .filter-badge-arrondissement {
    background-color: #E8F5E9;
    color: #1B5E20;
  }
  
  .filter-badge-sector {
    background-color: #E3F2FD;
    color: #0D47A1;
  }
  
  .filter-badge-quartier {
    background-color: #F3E5F5;
    color: #4A148C;
  }
  
  .filter-badge-collector {
    background-color: #FFF3E0;
    color: #E65100;
  }
  
  .filter-badge-search {
    background-color: #FFEBEE;
    color: #B71C1C;
  }
  
  .pagination-nav {
    display: flex;
    align-items: center;
  }
  
  .pagination-pages {
    display: flex;
    align-items: center;
  }
  
  .pagination-button {
    min-width: 2rem;
    height: 2rem;
    margin: 0 0.125rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: #555;
    cursor: pointer;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .pagination-button:hover:not(:disabled) {
    background-color: rgba(230, 110, 40, 0.1);
  }
  
  .pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .pagination-page-button {
    font-weight: 500;
  }
  
  .pagination-active {
    background-color: #4a7c59 !important;
    color: white !important;
  }
  
  .pagination-nav-button {
    font-size: 1.25rem;
    padding: 0 0.5rem;
  }
  
  .pagination-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    color: #666;
  }
  
  .per-page-selector:focus {
    border-color: #e66e28;
    outline: none;
  }
  </style>