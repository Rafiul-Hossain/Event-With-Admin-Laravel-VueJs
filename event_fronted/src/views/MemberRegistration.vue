<template>


  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">Registration</div>
          <div class="card-body">
            <form @submit.prevent="memberRegistration">
              <div class="mb-3">
                <label for="name" class="form-label">Name</label> <input type="text" class="form-control" id="name" v-model="form.name" />
                <div v-if="errors.name" class="text-danger">{{ errors.name[0] }} </div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email"  v-model="form.email"/>
                <div v-if="errors.email" class="text-danger">{{ errors.email[0] }} </div>
              </div>
              <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="phone" class="form-control" id="phone" v-model="form.phone" />
                <div v-if="errors.phone" class="text-danger">{{ errors.phone[0] }} </div>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" v-model="form.password" />
                <div v-if="errors.password" class="text-danger">{{ errors.password[0] }} </div>
              </div>
              
              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label> <input type="password"
                  class="form-control" id="password_confirmation" v-model="form.password_confirmation" />
                <div v-if="errors.password_confirmation" class="text-danger">{{ errors.password_confirmation[0] }} </div>
              </div>
              <div class="mb-3">


              </div>
              <div class="mb-3">
                <label for="profile_image" class="form-label">Profile Image</label> <input type="file"
                  class="form-control" id="profile_image" @change="profileImageHandle"/>
                <div v-if="errors.profile_image" class="text-danger">{{ errors.profile_image[0] }} </div>
              </div>
              <button type="submit" class="btn btn-primary">Register</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

import { ref } from 'vue';
import api from '@/api/axios';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toast-notification';

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  profile_image: ''
});
const router = useRouter();
const toast = useToast();

const errors = ref({});

const profileImageHandle = (event) => {
  form.value.profile_image = event.target.files[0];
  
}
const memberRegistration = async() => {
  
    const formData = new FormData();
    for (const key in form.value) {
      formData.append(key, form.value[key]);
    }
  try {

    const response =  await api.post('/member-registration', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
   
    toast.success('Registration has been completed', {
      position: 'bottom-right',
      duration: 5000,
      
    });
    setTimeout(() => {
      router.push('/login').then(() => {
        return window.location.reload();
        
      });
    }, 2000);
   // router.push('/login').then(() => {
      //return window.location.reload();
    //});
  } catch (error) {
   toast.error('validation error',{
    position: 'top-right',
   
   })
   errors.value = error.response.data.errors
    
}
};

</script>