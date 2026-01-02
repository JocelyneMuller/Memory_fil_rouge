<template>
  <Transition name="modal">
    <div v-if="show" class="modal-overlay" @click.self="cancel">
      <div class="modal-container">
        <!-- Header -->
        <div class="modal-header">
          <div class="icon-warning">⚠️</div>
          <h3 class="modal-title">{{ title }}</h3>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <p>{{ message }}</p>
        </div>

        <!-- Footer avec boutons -->
        <div class="modal-footer">
          <button @click="cancel" class="btn-cancel">
            Annuler
          </button>
          <button @click="confirm" class="btn-confirm">
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
export default {
  name: "ConfirmModal",
  
  props: {
    show: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      default: 'Confirmation'
    },
    message: {
      type: String,
      required: true
    },
    confirmText: {
      type: String,
      default: 'Confirmer'
    }
  },
  
  emits: ['confirm', 'cancel'],
  
  methods: {
    confirm() {
      this.$emit('confirm');
    },
    
    cancel() {
      this.$emit('cancel');
    }
  },
  
  mounted() {
    document.addEventListener('keydown', this.handleEscape);
  },
  
  unmounted() {
    document.removeEventListener('keydown', this.handleEscape);
  },
  
  methods: {
    confirm() {
      this.$emit('confirm');
    },
    
    cancel() {
      this.$emit('cancel');
    },
    
    handleEscape(event) {
      if (event.key === 'Escape' && this.show) {
        this.cancel();
      }
    }
  }
}
</script>

<style scoped>
/* Overlay (fond sombre semi-transparent) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  backdrop-filter: blur(2px);
}

/* Container de la modal */
.modal-container {
  background-color: #fff;
  border: 2px solid #000;
  border-radius: 25px;
  max-width: 500px;
  width: 90%;
  padding: 40px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
  animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.icon-warning {
  font-size: 40px;
  line-height: 1;
}

.modal-title {
  font-size: 24px;
  font-weight: bold;
  color: #000;
  margin: 0;
}

/* Body */
.modal-body {
  margin-bottom: 30px;
}

.modal-body p {
  font-size: 16px;
  line-height: 1.6;
  color: #333;
  margin: 0;
}

/* Footer avec boutons */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
}

/* Bouton Annuler */
.btn-cancel {
  background-color: #fff;
  color: #333;
  border: 2px solid #000;
  border-radius: 25px;
  padding: 14px 40px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-cancel:hover {
  background-color: #f8f9fa;
  transform: translateY(-2px);
}

/* Bouton Confirmer (rouge-corail) */
.btn-confirm {
  background-color: #ff584a;
  color: #fff;
  border: 2px solid #ff584a;
  border-radius: 25px;
  padding: 14px 40px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-confirm:hover {
  background-color: #ff4435;
  transform: translateY(-2px);
}

.btn-confirm:active {
  transform: scale(0.98);
}

/* Animations d'entrée/sortie */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
