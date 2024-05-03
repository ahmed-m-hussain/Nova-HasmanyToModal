import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import ResourceIndexModal from './index'
import CreateRelationModal from './CreateRelationModal'
//import CreateResource from './Create'

Nova.booting((app, store) => {
  app.component('index-hasmany-to-modal', IndexField)
  app.component('detail-hasmany-to-modal', DetailField)
  app.component('ResourceIndexModal', ResourceIndexModal)
  app.component('CustomCreateRelationModal', CreateRelationModal)
 // app.component('CreateResource', CreateResource)
 // app.component('form-hasmany-to-modal', FormField)
})
