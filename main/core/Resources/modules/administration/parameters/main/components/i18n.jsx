import React from 'react'
import {PropTypes as T} from 'prop-types'
import {connect} from 'react-redux'

import {trans} from '#/main/app/intl/translation'
import {URL_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {selectors} from '#/main/core/administration/parameters/main/store'

const I18nComponent = (props) =>
  <FormData
    level={2}
    title={trans('language')}
    name={selectors.FORM_NAME}
    target={['apiv2_parameters_update']}
    buttons={true}
    cancel={{
      type: URL_BUTTON,
      target: ['claro_admin_open']
    }}
    sections={[
      {
        title: trans('general'),
        fields: [
          {
            name: 'locales.available',
            type: 'locale',
            label: trans('available_languages'),
            options: {
              available: props.availableLocales,
              multiple: true
            }
          }, {
            name: 'locales.default',
            type: 'locale',
            label: trans('default_language'),
            options: {
              available: props.availableLocales
            }
          }
        ]
      }
    ]}
  />

I18nComponent.propTypes = {
  availableLocales: T.arrayOf(T.string).isRequired,
  locales: T.shape({
    available: T.arrayOf(T.string),
    default: T.string
  })
}

const I18n = connect(
  (state) => ({
    availableLocales: selectors.availableLocales(state)
  })
)(I18nComponent)

export {
  I18n
}