import {PropTypes as T} from 'prop-types'

import {Widget} from '#/main/core/widget/prop-types'

const Tab = {
  propTypes: {
    id: T.string.isRequired,
    type: T.oneOf(['workspace', 'admin_desktop', 'desktop', 'administration', 'home', 'admin']),
    title: T.string.isRequired,
    longTitle: T.string,
    slug: T.string.isRequired,
    centerTitle: T.bool.isRequired,
    icon: T.string,
    poster: T.oneOfType([
      T.string,
      T.object
    ]),
    position: T.number,
    restrictions: T.shape({
      hidden: T.bool,
      roles: T.arrayOf(T.shape({
        // TODO : role types
      }))
    }),
    widgets: T.arrayOf(T.shape(
      Widget.propTypes
    ))
  },
  defaultProps: {
    icon: null,
    poster: null,
    widgets: [],
    centerTitle: false,
    restrictions: {
      hidden: false,
      roles: []
    }
  }
}

export {
  Tab
}
